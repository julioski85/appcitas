<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\BloqueoHorario;
use App\Models\Programa;

class CitaController extends Controller
{
    private function mustClosePastAppointment(array $data): bool
    {
        return Cita::hasElapsed($data['fecha'], $data['hora_fin'])
            && in_array($data['estatus'], ['agendada', 'confirmada'], true);
    }

    private function buildOverdueMessage(): string
    {
        return 'Esta cita ya ocurrió y no puede permanecer en Agendada/Confirmada. Debes marcarla como Asistió o No asistió.';
    }

    private function normalizeAndValidateCita(array $input, array $user, ?int $ignoreId = null, bool $isUpdate = false): array
    {
        $data = $this->validate([
            'sucursal_id' => 'required',
            'servicio' => 'required|in:' . implode(',', Cita::SERVICIOS),
            'fecha' => 'required|date',
            'hora_inicio' => 'required|time',
            'hora_fin' => 'required|time',
            'estatus' => 'required|in:agendada,confirmada,asistio,no_asistio,cancelada,reprogramada',
            'programa_id' => '',
            'origen' => 'required|in:call_center,sucursal,web',
            'cliente_mode' => 'required|in:existente,manual',
        ], $input);

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
            $data['origen'] = 'sucursal';
        }

        $codigoPromocion = trim((string)($input['codigo_promocion'] ?? ''));
        $data['codigo_promocion'] = $codigoPromocion === '' ? null : $codigoPromocion;
        $data['programa_id'] = trim((string)($input['programa_id'] ?? ''));
        $clienteMode = $data['cliente_mode'];
        $data['cliente_id'] = trim((string)($input['cliente_id'] ?? ''));

        if ($clienteMode === 'existente') {
            if ($data['cliente_id'] === '') {
                set_flash('error', 'Debes seleccionar un cliente existente válido de la lista.');
                set_old($input);
                back();
            }

            $cliente = Cliente::find((int)$data['cliente_id']);
            if (!$cliente) {
                set_flash('error', 'El cliente seleccionado no existe.');
                set_old($input);
                back();
            }
            if ($user['rol'] === 'sucursal' && (int)$cliente['sucursal_id'] !== (int)$user['sucursal_id']) {
                http_response_code(403);
                exit('No autorizado.');
            }
            $data['cliente_nombre'] = $cliente['nombre_completo'];
            $data['cliente_telefono'] = $cliente['telefono'];
        } else {
            $data['cliente_id'] = '';
            $base = $this->validate([
                'cliente_nombre' => 'required',
                'cliente_telefono' => 'required',
            ], $input);
            $data['cliente_nombre'] = $base['cliente_nombre'];
            $data['cliente_telefono'] = $base['cliente_telefono'];
        }

        if (strtotime($data['fecha'] . ' ' . $data['hora_fin']) <= strtotime($data['fecha'] . ' ' . $data['hora_inicio'])) {
            set_flash('error', 'La hora fin debe ser mayor a la hora inicio.');
            set_old($input);
            back();
        }

        $today = date('Y-m-d');
        if (!$isUpdate && $data['fecha'] < $today) {
            set_flash('error', 'No se pueden agendar citas en fechas anteriores al día actual.');
            set_old($input);
            back();
        }

        if ($this->mustClosePastAppointment($data)) {
            set_flash('error', $this->buildOverdueMessage());
            set_old($input);
            back();
        }

        if ($data['programa_id'] !== '') {
            $programa = Programa::find((int)$data['programa_id']);
            if (!$programa) {
                set_flash('error', 'El programa seleccionado no existe.');
                set_old($input);
                back();
            }
            if ((int)$programa['activo'] !== 1) {
                set_flash('error', 'El programa seleccionado está inactivo.');
                set_old($input);
                back();
            }
            if (!empty($programa['sucursal_id']) && (int)$programa['sucursal_id'] !== (int)$data['sucursal_id']) {
                set_flash('error', 'El programa seleccionado no pertenece a la sucursal elegida.');
                set_old($input);
                back();
            }
        }

        $sucursal = Sucursal::find((int)$data['sucursal_id']);
        if (!$sucursal) {
            set_flash('error', 'La sucursal seleccionada no existe.');
            set_old($input);
            back();
        }

        if (!Sucursal::isRangeWithinBusinessHours($sucursal, $data['hora_inicio'], $data['hora_fin'])) {
            set_flash('error', 'El horario está fuera del rango permitido de la sucursal (' . Sucursal::openingHour($sucursal) . ' - ' . Sucursal::closingHour($sucursal) . ').');
            set_old($input);
            back();
        }

        $capacidad = max(1, (int)($sucursal['capacidad_simultanea'] ?? 1));

        if (Cita::hasCapacityConflict($data, $capacidad, $ignoreId)) {
            set_flash('error', 'Ese horario no tiene cupo disponible en la sucursal seleccionada.');
            set_old($input);
            back();
        }

        if (BloqueoHorario::hasBlockingForRange((int)$data['sucursal_id'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
            set_flash('error', 'Ese horario no está disponible en la sucursal seleccionada.');
            set_old($input);
            back();
        }

        return $data;
    }

    public function index(): void
    {
        $user = $this->requireAuth();
        $filters = [
            'sucursal_id' => $_GET['sucursal_id'] ?? '',
            'estatus' => $_GET['estatus'] ?? '',
            'start' => $_GET['start'] ?? '',
            'end' => $_GET['end'] ?? '',
        ];

        if ($user['rol'] === 'sucursal') {
            $filters['sucursal_id'] = (string)$user['sucursal_id'];
        }

        $sucursales = Sucursal::all();
        $selectedSucursal = null;
        if (!empty($filters['sucursal_id'])) {
            $selectedSucursal = Sucursal::find((int)$filters['sucursal_id']);
        } elseif ($user['rol'] === 'sucursal' && !empty($user['sucursal_id'])) {
            $selectedSucursal = Sucursal::find((int)$user['sucursal_id']);
        }

        $calendarOpenHour = $selectedSucursal ? Sucursal::openingHour($selectedSucursal) : '08:00';
        $calendarCloseHour = $selectedSucursal ? Sucursal::closingHour($selectedSucursal) : '20:00';

        $ultimasCitas = Cita::latest($user, $filters);
        $citasVencidasPendientes = Cita::overduePendingList($user, 12);

        $this->view('citas/index', compact('user', 'filters', 'sucursales', 'ultimasCitas', 'calendarOpenHour', 'calendarCloseHour', 'citasVencidasPendientes'));
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $sucursales = Sucursal::all();
        $sucursalInicial = (int)($_GET['sucursal_id'] ?? ($user['sucursal_id'] ?? 0));
        $sucursalConfig = $sucursalInicial > 0 ? Sucursal::find($sucursalInicial) : null;
        $defaultStart = $sucursalConfig ? Sucursal::openingHour($sucursalConfig) : '10:00';
        $defaultEnd = date('H:i', strtotime('2000-01-01 ' . $defaultStart . ' +30 minutes'));

        $cita = [
            'sucursal_id' => $_GET['sucursal_id'] ?? ($user['sucursal_id'] ?? ''),
            'cliente_id' => '',
            'cliente_nombre' => '',
            'cliente_telefono' => '',
            'servicio' => 'Consulta general',
            'fecha' => $_GET['date'] ?? date('Y-m-d'),
            'hora_inicio' => $_GET['time'] ?? $defaultStart,
            'hora_fin' => $_GET['end'] ?? $defaultEnd,
            'estatus' => 'agendada',
            'origen' => $user['rol'] === 'call_center' ? 'call_center' : ($user['rol'] === 'sucursal' ? 'sucursal' : 'web'),
            'codigo_promocion' => '',
            'programa_id' => '',
        ];
        $sucursalPrograma = (int)($cita['sucursal_id'] ?: ($user['sucursal_id'] ?? 0));
        $programas = $sucursalPrograma > 0 ? Programa::optionsForSucursal($sucursalPrograma) : [];
        $isEdit = false;
        $this->view('citas/form', compact('user', 'sucursales', 'cita', 'isEdit', 'programas'));
    }

    public function store(): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $data = $this->normalizeAndValidateCita($_POST, $user);
        Cita::create($data, (int)$user['id']);

        set_flash('success', 'Cita creada correctamente.');
        redirect('/citas');
    }

    public function edit(string $id): void
    {
        $user = $this->requireAuth();
        $sucursales = Sucursal::all();
        $cita = Cita::find((int)$id);

        if (!$cita) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$cita['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $programas = Programa::optionsForSucursal((int)$cita['sucursal_id'], !empty($cita['programa_id']) ? (int)$cita['programa_id'] : null);
        $isEdit = true;
        $this->view('citas/form', compact('user', 'sucursales', 'cita', 'isEdit', 'programas'));
    }

    public function update(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $existing = Cita::find((int)$id);
        if (!$existing) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$existing['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $data = $this->normalizeAndValidateCita($_POST, $user, (int)$id, true);
        Cita::update((int)$id, $data);

        set_flash('success', 'Cita actualizada correctamente.');
        redirect('/citas');
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $existing = Cita::find((int)$id);
        if (!$existing) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$existing['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        Cita::delete((int)$id);
        set_flash('success', 'Cita eliminada correctamente.');
        redirect('/citas');
    }

    public function cancel(string $id): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $existing = Cita::find((int)$id);
        if (!$existing) {
            set_flash('error', 'Cita no encontrada.');
            redirect('/citas');
        }

        if ($user['rol'] === 'sucursal' && (int)$existing['sucursal_id'] !== (int)$user['sucursal_id']) {
            http_response_code(403);
            exit('No autorizado.');
        }

        Cita::cancel((int)$id);
        set_flash('success', 'Cita cancelada.');
        redirect('/citas');
    }

    public function searchClientes(): void
    {
        $user = $this->requireAuth();
        $query = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($query) < 2) {
            $this->json(['ok' => true, 'data' => []]);
        }

        try {
            $items = Cliente::allForSelect($user, $query);
            $data = array_map(static function (array $item): array {
                return [
                    'id' => (int)$item['id'],
                    'nombre_completo' => $item['nombre_completo'],
                    'telefono' => $item['telefono'],
                    'display' => $item['nombre_completo'] . ' · ' . $item['telefono'],
                ];
            }, $items);

            $this->json(['ok' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error de servidor al buscar clientes.'], 500);
        }
    }


    public function programasPorSucursal(): void
    {
        $user = $this->requireAuth();
        $sucursalId = (int)($_GET['sucursal_id'] ?? 0);
        if ($user['rol'] === 'sucursal') {
            $sucursalId = (int)$user['sucursal_id'];
        }
        if ($sucursalId <= 0) {
            $this->json(['ok' => true, 'data' => []]);
        }

        $items = Programa::optionsForSucursal($sucursalId);
        $this->json(['ok' => true, 'data' => $items]);
    }

    public function quickStoreProspecto(): void
    {
        $user = $this->requireAuth();
        verify_csrf();

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $data = [
            'sucursal_id' => trim((string)($payload['sucursal_id'] ?? '')),
            'nuevo_nombre_completo' => trim((string)($payload['nuevo_nombre_completo'] ?? '')),
            'nuevo_telefono' => trim((string)($payload['nuevo_telefono'] ?? '')),
            'nuevo_sexo' => trim((string)($payload['nuevo_sexo'] ?? '')),
            'nuevo_fecha_nacimiento' => trim((string)($payload['nuevo_fecha_nacimiento'] ?? '')),
            'nuevo_email' => trim((string)($payload['nuevo_email'] ?? '')),
            'nuevo_direccion' => trim((string)($payload['nuevo_direccion'] ?? '')),
            'nuevo_ciudad' => trim((string)($payload['nuevo_ciudad'] ?? '')),
            'nuevo_origen' => trim((string)($payload['nuevo_origen'] ?? '')),
            'nuevo_notas' => trim((string)($payload['nuevo_notas'] ?? '')),
        ];

        if ($user['rol'] === 'sucursal') {
            $data['sucursal_id'] = (string)$user['sucursal_id'];
        }

        if ($data['sucursal_id'] === '' || $data['nuevo_nombre_completo'] === '' || $data['nuevo_telefono'] === '' || $data['nuevo_sexo'] === '') {
            $this->json(['ok' => false, 'message' => 'Completa los campos obligatorios del prospecto.'], 422);
        }

        if (!in_array($data['nuevo_sexo'], ['masculino', 'femenino', 'otro'], true)) {
            $this->json(['ok' => false, 'message' => 'Sexo inválido.'], 422);
        }

        if ($data['nuevo_origen'] === '' || !in_array($data['nuevo_origen'], ['Redes sociales', 'Programa de televisión', 'Google', 'Otros'], true)) {
            $this->json(['ok' => false, 'message' => 'Selecciona un origen válido.'], 422);
        }

        if ($data['nuevo_email'] !== '' && !filter_var($data['nuevo_email'], FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'message' => 'Correo inválido.'], 422);
        }

        $tieneResponsable = ($payload['nuevo_tiene_responsable'] ?? '0') === '1' || ($payload['nuevo_tiene_responsable'] ?? '') === 'on';
        $responsableNombre = trim((string)($payload['nuevo_responsable_nombre'] ?? ''));
        $responsableTelefono = trim((string)($payload['nuevo_responsable_telefono'] ?? ''));
        $responsableParentesco = trim((string)($payload['nuevo_responsable_parentesco'] ?? ''));

        if ($tieneResponsable && ($responsableNombre === '' || $responsableTelefono === '' || $responsableParentesco === '')) {
            $this->json(['ok' => false, 'message' => 'Completa los datos del contacto responsable del nuevo prospecto.'], 422);
        }

        if (!$tieneResponsable) {
            $responsableNombre = null;
            $responsableTelefono = null;
            $responsableParentesco = null;
        }

        try {
            $clienteId = Cliente::create([
                'sucursal_id' => $data['sucursal_id'],
                'nombre_completo' => $data['nuevo_nombre_completo'],
                'telefono' => $data['nuevo_telefono'],
                'fecha_nacimiento' => $data['nuevo_fecha_nacimiento'],
                'sexo' => $data['nuevo_sexo'],
                'email' => $data['nuevo_email'],
                'direccion' => $data['nuevo_direccion'],
                'ciudad' => $data['nuevo_ciudad'],
                'origen' => $data['nuevo_origen'],
                'tiene_responsable' => $tieneResponsable ? '1' : '0',
                'responsable_nombre' => $responsableNombre,
                'responsable_telefono' => $responsableTelefono,
                'responsable_parentesco' => $responsableParentesco,
                'estatus_cliente' => 'prospecto',
                'notas' => $data['nuevo_notas'],
            ]);

            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                $this->json(['ok' => false, 'message' => 'No fue posible cargar el prospecto creado.'], 500);
            }

            $this->json([
                'ok' => true,
                'message' => 'Prospecto creado correctamente.',
                'data' => [
                    'id' => (int)$cliente['id'],
                    'nombre_completo' => $cliente['nombre_completo'],
                    'telefono' => $cliente['telefono'],
                    'display' => $cliente['nombre_completo'] . ' · ' . $cliente['telefono'],
                ],
            ], 201);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error de servidor al crear el prospecto.'], 500);
        }
    }

}

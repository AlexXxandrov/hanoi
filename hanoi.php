<?php
/**
 * =====================================================================
 *  TORRE DE HANOI — Herramienta de Enfoque Ejecutivo
 * ---------------------------------------------------------------------
 *  Componente autocontenido para intranet corporativa.
 *
 *  Arquitectura del archivo:
 *    [BLOQUE PHP]   -> Configuración del lado servidor (opcional).
 *    [BLOQUE HTML]  -> Estructura semántica con Bootstrap 5.
 *    [BLOQUE CSS]   -> Estilos personalizados del tablero (paleta sobria).
 *    [BLOQUE JS]    -> Lógica del juego, persistencia y leaderboard.
 *
 *  Dependencias: únicamente Bootstrap 5 (CDN). JS 100% vanilla.
 *  Persistencia: localStorage del navegador (sin base de datos).
 * =====================================================================
 */

// --- Configuración del lado servidor (ajustable según la intranet) ---
$config = [
    'titulo_app'   => 'Torre de Hanói',
    'subtitulo'    => 'Pausa estratégica · Enfoque y descompresión',
    'discos_min'   => 4,   // Mínimo obligado por requisito.
    'discos_max'   => 8,   // Tope razonable para mantener la sesión ágil.
    'discos_def'   => 4,
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['titulo_app']) ?> · Intranet</title>

    <!-- Bootstrap 5 (CSS) -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

    <!-- =====================  [BLOQUE CSS]  ===================== -->
    <style>
        :root {
            /* Paleta ejecutiva: azul marino profundo + grises pizarra */
            --hanoi-bg-1:        #0d1b2a;   /* Azul marino muy profundo  */
            --hanoi-bg-2:        #1b263b;   /* Gris pizarra azulado      */
            --hanoi-surface:     #16202e;   /* Superficie de panel       */
            --hanoi-surface-2:   #1f2c3d;   /* Superficie elevada        */
            --hanoi-border:      #2c3e50;   /* Bordes sutiles            */
            --hanoi-text:        #e6edf3;   /* Texto principal           */
            --hanoi-text-muted:  #8da2b5;   /* Texto secundario          */
            --hanoi-accent:      #c8a25b;   /* Bronce / dorado tenue     */
            --hanoi-accent-soft: #b08d4c;
            --hanoi-success:     #2f6b4f;   /* Verde bosque profundo     */
        }

        html, body {
            height: 100%;
        }

        body {
            background:
                radial-gradient(1200px 600px at 50% -10%, #20324a 0%, transparent 60%),
                linear-gradient(160deg, var(--hanoi-bg-1) 0%, var(--hanoi-bg-2) 100%);
            background-attachment: fixed;
            color: var(--hanoi-text);
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            letter-spacing: .2px;
            min-height: 100vh;
        }

        /* ---------- Cabecera ---------- */
        .hanoi-brand {
            font-weight: 600;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--hanoi-text);
        }
        .hanoi-brand .accent { color: var(--hanoi-accent); }
        .hanoi-subtitle {
            color: var(--hanoi-text-muted);
            font-size: .9rem;
            letter-spacing: 1.5px;
        }
        .hanoi-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--hanoi-border), transparent);
            border: 0;
            opacity: .9;
        }

        /* ---------- Paneles ---------- */
        .hanoi-panel {
            background: var(--hanoi-surface);
            border: 1px solid var(--hanoi-border);
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .35);
        }

        /* ---------- Marcador / métricas ---------- */
        .metric-card {
            background: linear-gradient(180deg, var(--hanoi-surface-2), var(--hanoi-surface));
            border: 1px solid var(--hanoi-border);
            border-radius: 12px;
            padding: .85rem 1rem;
            text-align: center;
        }
        .metric-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--hanoi-text-muted);
        }
        .metric-value {
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--hanoi-text);
            font-variant-numeric: tabular-nums;
        }
        .metric-value.accent { color: var(--hanoi-accent); }

        /* ---------- Tablero ---------- */
        .hanoi-board {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            gap: clamp(10px, 3vw, 40px);
            padding: 28px clamp(10px, 3vw, 32px) 0;
            min-height: 340px;
            user-select: none;
        }

        .peg {
            position: relative;
            flex: 1 1 0;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            min-height: 300px;
            padding-bottom: 0;
            cursor: pointer;
            transition: transform .15s ease;
        }
        .peg:hover { transform: translateY(-2px); }

        /* Mástil de la torre: simula aluminio cepillado */
        .peg::before {
            content: "";
            position: absolute;
            bottom: 22px;
            width: 12px;
            height: 250px;
            border-radius: 6px;
            background: linear-gradient(90deg,
                #6b7682 0%, #aab6c2 18%, #dfe7ee 38%,
                #b9c4cf 50%, #aab6c2 62%, #6b7682 100%);
            background-size: 6px 100%;
            box-shadow: inset 0 0 4px rgba(0,0,0,.4), 0 2px 6px rgba(0,0,0,.4);
            z-index: 0;
        }

        /* Base de la torre */
        .peg-base {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 22px;
            border-radius: 8px;
            background: linear-gradient(180deg, #5a6470 0%, #39424d 60%, #2a323b 100%);
            box-shadow: inset 0 1px 1px rgba(255,255,255,.15), 0 6px 14px rgba(0,0,0,.45);
        }

        .peg-stack {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            flex-direction: column-reverse; /* primero abajo */
            align-items: center;
            gap: 6px;
            padding-bottom: 6px;
        }

        .peg-label {
            margin-top: 12px;
            font-size: .72rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--hanoi-text-muted);
        }

        /* Resaltado de torre origen/destino seleccionada o válida */
        .peg.selected::before {
            background: linear-gradient(90deg, #8a6f33, #d8b66a, #8a6f33);
            box-shadow: 0 0 14px rgba(200,162,91,.6);
        }
        .peg.drop-ok   { outline: 2px dashed rgba(200,162,91,.55); outline-offset: 10px; border-radius: 14px; }
        .peg.drop-bad  { outline: 2px dashed rgba(180, 70, 70, .55); outline-offset: 10px; border-radius: 14px; }

        /* ---------- Discos ---------- */
        .disk {
            height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 600;
            color: rgba(255,255,255,.92);
            text-shadow: 0 1px 2px rgba(0,0,0,.5);
            box-shadow:
                inset 0 1px 1px rgba(255,255,255,.25),
                inset 0 -2px 4px rgba(0,0,0,.35),
                0 4px 10px rgba(0,0,0,.45);
            border: 1px solid rgba(255,255,255,.08);
            cursor: grab;
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }
        .disk:active { cursor: grabbing; }
        .disk.is-top { }
        .disk.lifted {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 0 16px rgba(200,162,91,.55), 0 8px 16px rgba(0,0,0,.5);
        }
        .disk.dragging { opacity: .45; }
        .disk:not(.is-top) { cursor: not-allowed; }

        /* Gradientes ejecutivos por color (se asignan ciclicamente) */
        .disk-c0 { background: linear-gradient(180deg, #25416b 0%, #15263f 100%); } /* azul medianoche */
        .disk-c1 { background: linear-gradient(180deg, #4a5562 0%, #2c333c 100%); } /* gris oxford     */
        .disk-c2 { background: linear-gradient(180deg, #c79a4f 0%, #8a6326 100%); } /* bronce          */
        .disk-c3 { background: linear-gradient(180deg, #2f6b4f 0%, #18402e 100%); } /* verde bosque    */
        .disk-c4 { background: linear-gradient(180deg, #3a4a63 0%, #222d3f 100%); } /* azul acero      */
        .disk-c5 { background: linear-gradient(180deg, #6b5a3a 0%, #403420 100%); } /* madera oscura   */

        /* ---------- Botones / acentos ---------- */
        .btn-executive {
            background: linear-gradient(180deg, var(--hanoi-accent), var(--hanoi-accent-soft));
            border: none;
            color: #1a1305;
            font-weight: 600;
            letter-spacing: .5px;
        }
        .btn-executive:hover { filter: brightness(1.07); color: #1a1305; }
        .btn-outline-soft {
            border: 1px solid var(--hanoi-border);
            color: var(--hanoi-text);
            background: transparent;
        }
        .btn-outline-soft:hover {
            background: var(--hanoi-surface-2);
            color: var(--hanoi-text);
            border-color: var(--hanoi-accent);
        }

        /* ---------- Leaderboard ---------- */
        .table-hanoi {
            --bs-table-bg: transparent;
            --bs-table-color: var(--hanoi-text);
            color: var(--hanoi-text);
        }
        .table-hanoi thead th {
            color: var(--hanoi-text-muted);
            text-transform: uppercase;
            font-size: .68rem;
            letter-spacing: 1.5px;
            border-bottom: 1px solid var(--hanoi-border) !important;
            font-weight: 600;
        }
        .table-hanoi td {
            border-color: rgba(255,255,255,.05) !important;
            font-variant-numeric: tabular-nums;
            vertical-align: middle;
        }
        .table-hanoi tr.top-rank td { color: var(--hanoi-accent); font-weight: 600; }

        .rank-badge {
            display: inline-block;
            min-width: 34px;
            padding: .2rem .45rem;
            border-radius: 6px;
            font-weight: 700;
            font-size: .75rem;
            letter-spacing: 1px;
            text-align: center;
        }
        .rank-AAA { background: rgba(200,162,91,.18); color: #e2c485; border: 1px solid rgba(200,162,91,.4); }
        .rank-AA  { background: rgba(120,160,210,.16); color: #b9cdf0; border: 1px solid rgba(120,160,210,.35); }
        .rank-A   { background: rgba(120,180,150,.16); color: #b8e0cb; border: 1px solid rgba(120,180,150,.35); }
        .rank-B   { background: rgba(150,160,170,.14); color: #c3ccd6; border: 1px solid rgba(150,160,170,.3); }

        /* ---------- Modales ---------- */
        .modal-content.hanoi-modal {
            background: linear-gradient(180deg, var(--hanoi-surface-2), var(--hanoi-surface));
            border: 1px solid var(--hanoi-border);
            color: var(--hanoi-text);
            border-radius: 16px;
        }
        .victory-phrase {
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 700;
            letter-spacing: .5px;
            background: linear-gradient(90deg, #e2c485, #c8a25b, #b9cdf0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .victory-metric { text-align: center; }
        .victory-metric .v { font-size: 1.8rem; font-weight: 700; color: var(--hanoi-accent); }
        .victory-metric .l { font-size: .7rem; letter-spacing: 2px; text-transform: uppercase; color: var(--hanoi-text-muted); }

        /* Selector de discos */
        .disk-pick .btn-check:checked + .btn {
            background: linear-gradient(180deg, var(--hanoi-accent), var(--hanoi-accent-soft));
            color: #1a1305;
            border-color: var(--hanoi-accent);
        }

        .text-muted-exec { color: var(--hanoi-text-muted) !important; }

        @media (max-width: 575.98px) {
            .metric-value { font-size: 1.25rem; }
            .hanoi-board { min-height: 260px; }
            .peg::before { height: 200px; }
        }
    </style>
</head>
<body>

<!-- =====================  [BLOQUE HTML]  ===================== -->
<div class="container py-4 py-lg-5">

    <!-- Cabecera -->
    <header class="text-center mb-4">
        <h1 class="hanoi-brand h3 mb-1">
            Torre de <span class="accent">Hanói</span>
        </h1>
        <p class="hanoi-subtitle mb-3"><?= htmlspecialchars($config['subtitulo']) ?></p>
        <hr class="hanoi-divider w-50 mx-auto">
    </header>

    <div class="row g-4 justify-content-center">

        <!-- ============ COLUMNA PRINCIPAL: JUEGO ============ -->
        <div class="col-12 col-xl-8">

            <!-- Pantalla de configuración (menú de selección de discos) -->
            <section id="setupScreen" class="hanoi-panel p-4 p-lg-5 text-center">
                <h2 class="h5 mb-2">Configura tu sesión</h2>
                <p class="text-muted-exec mb-4">
                    Selecciona el número de discos. A mayor cantidad, mayor reto cognitivo.
                </p>

                <div class="disk-pick d-flex flex-wrap justify-content-center gap-2 mb-4" id="diskPicker">
                    <!-- Generado dinámicamente por JS según rango PHP -->
                </div>

                <div class="text-muted-exec small mb-4">
                    Movimientos mínimos teóricos:
                    <span class="text-light" id="setupIdeal">—</span>
                    <span class="opacity-50">(2<sup>n</sup> − 1)</span>
                </div>

                <button class="btn btn-executive btn-lg px-5" id="btnStart">
                    Comenzar
                </button>
            </section>

            <!-- Pantalla de juego (oculta hasta iniciar) -->
            <section id="gameScreen" class="hanoi-panel p-3 p-lg-4 d-none">

                <!-- Marcador -->
                <div class="row g-2 g-md-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="metric-card">
                            <div class="metric-label">Movimientos</div>
                            <div class="metric-value" id="mUser">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="metric-card">
                            <div class="metric-label">Ideales</div>
                            <div class="metric-value" id="mIdeal">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="metric-card">
                            <div class="metric-label">Puntaje</div>
                            <div class="metric-value accent" id="mScore">1000</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="metric-card">
                            <div class="metric-label">Tiempo</div>
                            <div class="metric-value" id="mTime">00:00</div>
                        </div>
                    </div>
                </div>

                <!-- Tablero -->
                <div class="hanoi-board" id="board">
                    <!-- 3 torres generadas por JS -->
                </div>

                <!-- Pie de juego -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 gap-2">
                    <small class="text-muted-exec">
                        Arrastra el disco superior o haz clic en las torres para moverlo.
                    </small>
                    <button class="btn btn-outline-soft" id="btnReset">
                        Reiniciar juego
                    </button>
                </div>
            </section>
        </div>

        <!-- ============ COLUMNA LATERAL: LEADERBOARD ============ -->
        <div class="col-12 col-xl-4">
            <section class="hanoi-panel p-3 p-lg-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h2 class="h6 mb-0">Tabla del día</h2>
                    <span class="badge text-bg-dark border" id="todayLabel">—</span>
                </div>
                <p class="text-muted-exec small mb-3">
                    Mejores marcas de hoy. Se reinicia automáticamente a las 00:00 h.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-hanoi align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mov.</th>
                                <th>Puntaje</th>
                                <th>Rango</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody id="leaderBody">
                            <!-- Filas por JS -->
                        </tbody>
                    </table>
                </div>

                <div id="leaderEmpty" class="text-center text-muted-exec small py-4">
                    Aún no hay marcas hoy.<br>Completa una partida para inaugurar la tabla.
                </div>
            </section>
        </div>
    </div>

    <footer class="text-center mt-5">
        <small class="text-muted-exec">Herramienta interna de bienestar · Datos guardados localmente en tu navegador</small>
    </footer>
</div>

<!-- ============ MODAL: Confirmación de reinicio ============ -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content hanoi-modal">
            <div class="modal-body text-center p-4">
                <h3 class="h5 mb-3">Confirmar reinicio</h3>
                <p class="text-muted-exec mb-4">
                    ¿Estás seguro de que deseas reiniciar? Perderás tu avance actual.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-soft px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-executive px-4" id="btnConfirmReset">Sí, reiniciar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL: Victoria ============ -->
<div class="modal fade" id="victoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content hanoi-modal">
            <div class="modal-body text-center p-4 p-lg-5">
                <p class="victory-phrase mb-4">Ganaste ¿te sientes mejor?</p>

                <div class="row g-3 justify-content-center mb-4">
                    <div class="col-4 victory-metric">
                        <div class="v" id="vMoves">0</div>
                        <div class="l">Movimientos</div>
                    </div>
                    <div class="col-4 victory-metric">
                        <div class="v" id="vScore">0</div>
                        <div class="l">Puntaje</div>
                    </div>
                    <div class="col-4 victory-metric">
                        <div class="v" id="vRank">—</div>
                        <div class="l">Rango</div>
                    </div>
                </div>

                <p class="text-muted-exec small mb-4" id="vSummary"></p>

                <button class="btn btn-executive btn-lg px-5" id="btnVictoryClose">
                    Volver al menú
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 (JS Bundle) -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>

<!-- =====================  [BLOQUE JS]  ===================== -->
<script>
(function () {
    "use strict";

    /* ---------------------------------------------------------------
     *  Parámetros inyectados desde PHP
     * ------------------------------------------------------------- */
    const CFG = {
        min:  <?= (int)$config['discos_min'] ?>,
        max:  <?= (int)$config['discos_max'] ?>,
        def:  <?= (int)$config['discos_def'] ?>,
    };

    const LS_KEY = "hanoi_leaderboard_v1";  // Clave de persistencia local.

    /* ---------------------------------------------------------------
     *  Estado del juego
     * ------------------------------------------------------------- */
    const state = {
        numDisks:  CFG.def,
        pegs:      [[], [], []],   // Cada peg: array de tamaños, mayor al fondo.
        moves:     0,
        ideal:     0,
        selected:  null,           // Índice de torre seleccionada (modo clic).
        active:    false,
        startTime: null,
        timerId:   null,
    };

    /* ---------------------------------------------------------------
     *  Referencias DOM
     * ------------------------------------------------------------- */
    const el = {
        setupScreen: document.getElementById("setupScreen"),
        gameScreen:  document.getElementById("gameScreen"),
        diskPicker:  document.getElementById("diskPicker"),
        setupIdeal:  document.getElementById("setupIdeal"),
        btnStart:    document.getElementById("btnStart"),
        board:       document.getElementById("board"),
        mUser:       document.getElementById("mUser"),
        mIdeal:      document.getElementById("mIdeal"),
        mScore:      document.getElementById("mScore"),
        mTime:       document.getElementById("mTime"),
        btnReset:    document.getElementById("btnReset"),
        leaderBody:  document.getElementById("leaderBody"),
        leaderEmpty: document.getElementById("leaderEmpty"),
        todayLabel:  document.getElementById("todayLabel"),
        // Victoria
        vMoves:   document.getElementById("vMoves"),
        vScore:   document.getElementById("vScore"),
        vRank:    document.getElementById("vRank"),
        vSummary: document.getElementById("vSummary"),
        btnVictoryClose: document.getElementById("btnVictoryClose"),
        // Reset
        btnConfirmReset: document.getElementById("btnConfirmReset"),
    };

    const resetModal   = new bootstrap.Modal(document.getElementById("resetModal"));
    const victoryModal = new bootstrap.Modal(document.getElementById("victoryModal"));

    const TOWER_LABELS = ["Origen", "Auxiliar", "Destino"];

    /* ===============================================================
     *  UTILIDADES
     * ============================================================= */

    // Movimientos ideales: 2^n - 1
    const idealMoves = (n) => Math.pow(2, n) - 1;

    // Fecha local en formato YYYY-MM-DD (para reseteo diario).
    function todayKey() {
        const d = new Date();
        const p = (x) => String(x).padStart(2, "0");
        return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
    }

    // Hora exacta HH:MM:SS.
    function nowTime() {
        const d = new Date();
        const p = (x) => String(x).padStart(2, "0");
        return `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
    }

    /**
     * Puntaje de eficiencia.
     * Parte de 1000 puntos y disminuye proporcionalmente con cada
     * movimiento excedente respecto al ideal: score = 1000 * ideal / usados.
     */
    function computeScore(userMoves, ideal) {
        if (userMoves <= 0) return 1000;
        const score = Math.round(1000 * (ideal / userMoves));
        return Math.max(0, Math.min(1000, score));
    }

    /**
     * Calificación ejecutiva según la eficiencia alcanzada.
     */
    function computeRank(userMoves, ideal) {
        const eff = ideal / userMoves;           // 1.0 = perfecto.
        if (userMoves === ideal) return "AAA";   // Solución óptima exacta.
        if (eff >= 0.85)         return "AA";
        if (eff >= 0.65)         return "A";
        return "B";
    }

    /* ===============================================================
     *  PERSISTENCIA — localStorage con reseteo diario
     * ============================================================= */

    // Lee la tabla; si el día almacenado no es hoy, la limpia (reset 00:00).
    function loadLeaderboard() {
        let data = null;
        try { data = JSON.parse(localStorage.getItem(LS_KEY)); } catch (e) { data = null; }

        const today = todayKey();
        if (!data || data.date !== today) {
            data = { date: today, entries: [] };
            localStorage.setItem(LS_KEY, JSON.stringify(data));
        }
        return data;
    }

    function saveLeaderboard(data) {
        localStorage.setItem(LS_KEY, JSON.stringify(data));
    }

    // Inserta una marca y ordena por puntaje (desc), luego por movimientos (asc).
    function addLeaderboardEntry(entry) {
        const data = loadLeaderboard();
        data.entries.push(entry);
        data.entries.sort((a, b) =>
            (b.score - a.score) || (a.moves - b.moves)
        );
        data.entries = data.entries.slice(0, 10); // Top 10 del día.
        saveLeaderboard(data);
        renderLeaderboard();
    }

    function renderLeaderboard() {
        const data = loadLeaderboard();
        el.todayLabel.textContent = data.date;
        el.leaderBody.innerHTML = "";

        if (!data.entries.length) {
            el.leaderEmpty.classList.remove("d-none");
            return;
        }
        el.leaderEmpty.classList.add("d-none");

        data.entries.forEach((en, i) => {
            const tr = document.createElement("tr");
            if (i === 0) tr.classList.add("top-rank");
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td>${en.moves}</td>
                <td>${en.score}</td>
                <td><span class="rank-badge rank-${en.rank}">${en.rank}</span></td>
                <td>${en.time}</td>`;
            el.leaderBody.appendChild(tr);
        });
    }

    /* ===============================================================
     *  CONFIGURACIÓN / MENÚ DE DISCOS
     * ============================================================= */

    function buildDiskPicker() {
        el.diskPicker.innerHTML = "";
        for (let n = CFG.min; n <= CFG.max; n++) {
            const id = "disk-opt-" + n;
            const input = document.createElement("input");
            input.type = "radio";
            input.className = "btn-check";
            input.name = "diskCount";
            input.id = id;
            input.value = n;
            if (n === state.numDisks) input.checked = true;
            input.addEventListener("change", () => {
                state.numDisks = n;
                el.setupIdeal.textContent = idealMoves(n);
            });

            const label = document.createElement("label");
            label.className = "btn btn-outline-soft px-3";
            label.setAttribute("for", id);
            label.textContent = n;

            el.diskPicker.appendChild(input);
            el.diskPicker.appendChild(label);
        }
        el.setupIdeal.textContent = idealMoves(state.numDisks);
    }

    /* ===============================================================
     *  RENDER DEL TABLERO
     * ============================================================= */

    // Ancho de un disco según su tamaño (1..n).
    function diskWidth(size) {
        const minPct = 34;            // disco más pequeño.
        const maxPct = 100;           // disco más grande.
        const step = (maxPct - minPct) / Math.max(1, state.numDisks - 1);
        return minPct + step * (size - 1);
    }

    function buildBoard() {
        el.board.innerHTML = "";
        for (let p = 0; p < 3; p++) {
            const peg = document.createElement("div");
            peg.className = "peg";
            peg.dataset.peg = p;

            const stack = document.createElement("div");
            stack.className = "peg-stack";
            stack.dataset.peg = p;

            const base = document.createElement("div");
            base.className = "peg-base";

            const label = document.createElement("div");
            label.className = "peg-label";
            label.textContent = TOWER_LABELS[p];

            peg.appendChild(stack);
            peg.appendChild(base);
            peg.appendChild(label);

            // --- Eventos de torre: clic e (drag targets) ---
            peg.addEventListener("click", () => onPegClick(p));

            peg.addEventListener("dragover", (e) => {
                e.preventDefault();
                const from = window.__dragFrom;
                if (from === null || from === undefined) return;
                peg.classList.add(isLegalMove(from, p) ? "drop-ok" : "drop-bad");
            });
            peg.addEventListener("dragleave", () => {
                peg.classList.remove("drop-ok", "drop-bad");
            });
            peg.addEventListener("drop", (e) => {
                e.preventDefault();
                peg.classList.remove("drop-ok", "drop-bad");
                const from = window.__dragFrom;
                if (from !== null && from !== undefined) tryMove(from, p);
                window.__dragFrom = null;
            });

            el.board.appendChild(peg);
        }
        renderDisks();
    }

    function renderDisks() {
        const stacks = el.board.querySelectorAll(".peg-stack");
        stacks.forEach((stack, p) => {
            stack.innerHTML = "";
            state.pegs[p].forEach((size, idx) => {
                const isTop = idx === state.pegs[p].length - 1;
                const disk = document.createElement("div");
                disk.className = "disk disk-c" + ((size - 1) % 6) + (isTop ? " is-top" : "");
                disk.style.width = diskWidth(size) + "%";
                disk.textContent = size;
                disk.dataset.size = size;

                // Resalta el disco "levantado" si su torre está seleccionada.
                if (isTop && state.selected === p) disk.classList.add("lifted");

                // Drag solo para el disco superior.
                if (isTop) {
                    disk.draggable = true;
                    disk.addEventListener("dragstart", (e) => {
                        window.__dragFrom = p;
                        disk.classList.add("dragging");
                        if (e.dataTransfer) {
                            e.dataTransfer.effectAllowed = "move";
                            e.dataTransfer.setData("text/plain", String(p));
                        }
                    });
                    disk.addEventListener("dragend", () => {
                        disk.classList.remove("dragging");
                        window.__dragFrom = null;
                        clearDropHints();
                    });
                }
                stack.appendChild(disk);
            });
        });
    }

    function clearDropHints() {
        el.board.querySelectorAll(".peg").forEach(pg =>
            pg.classList.remove("drop-ok", "drop-bad"));
    }

    function clearSelection() {
        state.selected = null;
        el.board.querySelectorAll(".peg").forEach(pg => pg.classList.remove("selected"));
        renderDisks();
    }

    /* ===============================================================
     *  LÓGICA DE MOVIMIENTOS
     * ============================================================= */

    function topDisk(pegIndex) {
        const stack = state.pegs[pegIndex];
        return stack.length ? stack[stack.length - 1] : Infinity;
    }

    // Regla clásica: un disco mayor no puede ir sobre uno menor.
    function isLegalMove(from, to) {
        if (from === to) return false;
        if (!state.pegs[from].length) return false;
        return topDisk(from) < topDisk(to);
    }

    function tryMove(from, to) {
        if (!state.active) return;
        if (!isLegalMove(from, to)) {
            // Feedback sutil de movimiento inválido.
            flashInvalid(to);
            clearSelection();
            return;
        }
        const disk = state.pegs[from].pop();
        state.pegs[to].push(disk);
        state.moves++;
        el.mUser.textContent = state.moves;
        el.mScore.textContent = computeScore(state.moves, state.ideal);
        clearSelection();
        checkVictory();
    }

    function flashInvalid(pegIndex) {
        const peg = el.board.querySelector(`.peg[data-peg="${pegIndex}"]`);
        if (!peg) return;
        peg.classList.add("drop-bad");
        setTimeout(() => peg.classList.remove("drop-bad"), 280);
    }

    // Modo clic: primer clic selecciona origen, segundo intenta el movimiento.
    function onPegClick(p) {
        if (!state.active) return;

        if (state.selected === null) {
            if (!state.pegs[p].length) return; // Torre vacía: nada que levantar.
            state.selected = p;
            const peg = el.board.querySelector(`.peg[data-peg="${p}"]`);
            peg.classList.add("selected");
            renderDisks();
            return;
        }

        if (state.selected === p) {
            clearSelection();   // Clic en la misma torre: cancela.
            return;
        }

        tryMove(state.selected, p);
    }

    /* ===============================================================
     *  TEMPORIZADOR
     * ============================================================= */

    function startTimer() {
        state.startTime = Date.now();
        stopTimer();
        state.timerId = setInterval(updateTimer, 1000);
        updateTimer();
    }
    function stopTimer() {
        if (state.timerId) { clearInterval(state.timerId); state.timerId = null; }
    }
    function elapsedSeconds() {
        return state.startTime ? Math.floor((Date.now() - state.startTime) / 1000) : 0;
    }
    function updateTimer() {
        const s = elapsedSeconds();
        const mm = String(Math.floor(s / 60)).padStart(2, "0");
        const ss = String(s % 60).padStart(2, "0");
        el.mTime.textContent = `${mm}:${ss}`;
    }

    /* ===============================================================
     *  CICLO DE JUEGO
     * ============================================================= */

    function startGame() {
        state.pegs = [[], [], []];
        // Torre origen: discos de mayor (fondo) a menor (cima).
        for (let s = state.numDisks; s >= 1; s--) state.pegs[0].push(s);

        state.moves = 0;
        state.ideal = idealMoves(state.numDisks);
        state.selected = null;
        state.active = true;

        el.mUser.textContent = "0";
        el.mIdeal.textContent = state.ideal;
        el.mScore.textContent = "1000";

        buildBoard();
        startTimer();

        el.setupScreen.classList.add("d-none");
        el.gameScreen.classList.remove("d-none");
    }

    function backToMenu() {
        state.active = false;
        stopTimer();
        clearSelection();
        el.gameScreen.classList.add("d-none");
        el.setupScreen.classList.remove("d-none");
    }

    function checkVictory() {
        // Victoria: todos los discos en la torre Destino (índice 2).
        if (state.pegs[2].length !== state.numDisks) return;

        state.active = false;
        stopTimer();

        const score = computeScore(state.moves, state.ideal);
        const rank  = computeRank(state.moves, state.ideal);
        const time  = nowTime();

        // Persistir en la tabla diaria local.
        addLeaderboardEntry({
            moves: state.moves,
            score: score,
            rank:  rank,
            time:  time,
        });

        // Poblamos el modal de victoria.
        el.vMoves.textContent = state.moves;
        el.vScore.textContent = score;
        el.vRank.textContent  = rank;

        const elapsed = el.mTime.textContent;
        let cierre;
        if (rank === "AAA") {
            cierre = "Eficiencia perfecta. Mente despejada y precisa.";
        } else if (rank === "AA") {
            cierre = "Excelente control. Casi impecable.";
        } else if (rank === "A") {
            cierre = "Buen desempeño. La calma rinde frutos.";
        } else {
            cierre = "Resuelto. Respira: lo importante es haber terminado.";
        }
        el.vSummary.textContent =
            `Ideales: ${state.ideal} · Tus movimientos: ${state.moves} · Tiempo: ${elapsed}. ${cierre}`;

        setTimeout(() => victoryModal.show(), 350);
    }

    /* ===============================================================
     *  EVENTOS GLOBALES
     * ============================================================= */

    el.btnStart.addEventListener("click", startGame);

    el.btnReset.addEventListener("click", () => resetModal.show());

    el.btnConfirmReset.addEventListener("click", () => {
        resetModal.hide();
        backToMenu();
    });

    el.btnVictoryClose.addEventListener("click", () => {
        victoryModal.hide();
        backToMenu();
    });

    // Limpia pistas de arrastre si se suelta fuera de una torre.
    document.addEventListener("dragend", () => { window.__dragFrom = null; clearDropHints(); });

    /* ===============================================================
     *  INICIALIZACIÓN
     * ============================================================= */
    state.numDisks = CFG.def;
    buildDiskPicker();
    renderLeaderboard();
})();
</script>
</body>
</html>

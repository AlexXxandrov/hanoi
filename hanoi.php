<?php
/**
 * =====================================================================
 *  PORTAL DE OPTIMIZACIÓN COGNITIVA — Torre de Hanói
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
    'titulo_app'   => 'Portal de Optimización Cognitiva',
    'subtitulo'    => 'Gimnasio Mental — Pausa Activa',
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
            /* ----- Paleta base del ambiente ----- */
            --c-ink:       #022626;   /* Verde azulado muy oscuro (fondo)    */
            --c-graphite:  #33312E;   /* Gris Oxford / café grafito          */
            --c-wine:      #960048;   /* Burdeos / vino institucional        */
            --c-sage:      #B5C99A;   /* Verde sabio / oliva claro           */
            --c-bone:      #EDDDD4;   /* Blanco hueso / beige corporativo    */

            /* ----- Paleta de contraste (KPIs, bordes, estados) ----- */
            --c-gray:      #454545;   /* Gris medio oscuro                   */
            --c-wine2:     #96134B;   /* Vino complementario                 */
            --c-aluminum:  #88898C;   /* Gris aluminio / acero               */
            --c-bronze:    #BB945C;   /* Bronce / ocre corporativo           */
            --c-gold:      #EFC18B;   /* Oro viejo / arena claro             */

            /* ----- Roles de interfaz ----- */
            --bg-1:        var(--c-ink);
            --bg-2:        #04302f;
            --surface:     #073735;
            --surface-2:   #0c4341;
            --border:      rgba(136, 137, 140, .28);
            --text:        var(--c-bone);
            --text-muted:  #9fb094;
            --accent:      var(--c-bronze);
            --accent-soft: #a07c44;
        }

        html, body { height: 100%; }

        body {
            background:
                radial-gradient(1100px 560px at 50% -12%, #0a4a47 0%, transparent 60%),
                linear-gradient(160deg, var(--bg-1) 0%, var(--bg-2) 100%);
            background-attachment: fixed;
            color: var(--text);
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            letter-spacing: .2px;
            min-height: 100vh;
        }

        /* ---------- Cabecera ---------- */
        .hanoi-brand {
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--text);
        }
        .hanoi-brand .accent { color: var(--accent); }
        .hanoi-subtitle {
            color: var(--text-muted);
            font-size: .92rem;
            letter-spacing: 1.5px;
        }
        .hanoi-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            border: 0;
            opacity: .55;
        }

        /* ---------- Paneles ---------- */
        .hanoi-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .4);
        }

        /* ---------- Marcador / métricas (KPIs) ---------- */
        .metric-card {
            background: linear-gradient(180deg, var(--surface-2), var(--surface));
            border: 1px solid rgba(187, 148, 92, .35);  /* borde bronce sutil */
            border-radius: 12px;
            padding: .85rem 1rem;
            text-align: center;
        }
        .metric-label {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
        }
        .metric-value {
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }
        .metric-value.accent { color: var(--c-gold); }

        /* ---------- Tablero ---------- */
        .hanoi-board {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            gap: clamp(10px, 3vw, 40px);
            padding: 28px clamp(10px, 3vw, 32px) 0;
            min-height: 340px;
            user-select: none;
            background:
                linear-gradient(180deg, rgba(51,49,46,.35), rgba(51,49,46,.12));
            border: 1px solid var(--border);
            border-radius: 12px;
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
            cursor: pointer;
            transition: transform .15s ease;
        }
        .peg:hover { transform: translateY(-2px); }

        /* Mástil de la torre: aluminio cepillado */
        .peg::before {
            content: "";
            position: absolute;
            bottom: 22px;
            width: 12px;
            height: 250px;
            border-radius: 6px;
            background: linear-gradient(90deg,
                #5f6063 0%, #9fa0a3 18%, #d6d7d9 38%,
                #b6b7ba 50%, #9fa0a3 62%, #5f6063 100%);
            background-size: 6px 100%;
            box-shadow: inset 0 0 4px rgba(0,0,0,.45), 0 2px 6px rgba(0,0,0,.45);
            z-index: 0;
        }

        /* Base de la torre */
        .peg-base {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 22px;
            border-radius: 8px;
            background: linear-gradient(180deg, #4c4a45 0%, #38362f 60%, #2a2925 100%);
            box-shadow: inset 0 1px 1px rgba(255,255,255,.12), 0 6px 14px rgba(0,0,0,.5);
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
            color: var(--text-muted);
        }

        /* Resaltado de torre seleccionada o destino válido */
        .peg.selected::before {
            background: linear-gradient(90deg, #8a6f33, var(--c-gold), #8a6f33);
            box-shadow: 0 0 14px rgba(239,193,139,.65);
        }
        .peg.drop-ok   { outline: 2px dashed rgba(181, 201, 154, .7);  outline-offset: 10px; border-radius: 14px; }
        .peg.drop-bad  { outline: 2px dashed rgba(150, 19, 75, .7);    outline-offset: 10px; border-radius: 14px; }

        /* ---------- Discos ---------- */
        .disk {
            height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            font-weight: 700;
            color: rgba(255,255,255,.92);
            text-shadow: 0 1px 2px rgba(0,0,0,.5);
            box-shadow:
                inset 0 1px 1px rgba(255,255,255,.22),
                inset 0 -2px 4px rgba(0,0,0,.35),
                0 4px 10px rgba(0,0,0,.5);
            border: 1px solid rgba(255,255,255,.08);
            cursor: grab;
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
        }
        .disk:active { cursor: grabbing; }
        .disk.lifted {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 0 16px rgba(239,193,139,.6), 0 8px 16px rgba(0,0,0,.5);
        }
        .disk.dragging { opacity: .45; }
        .disk:not(.is-top) { cursor: not-allowed; }

        /* Gradientes con la paleta corporativa (se asignan cíclicamente) */
        .disk-c0 { background: linear-gradient(180deg, #b21059, #960048); }                       /* burdeos     */
        .disk-c1 { background: linear-gradient(180deg, #48453f, #33312E); }                       /* grafito     */
        .disk-c2 { background: linear-gradient(180deg, #d2ad77, #BB945C); color:#2c1f0c; text-shadow:0 1px 1px rgba(255,255,255,.25);} /* bronce */
        .disk-c3 { background: linear-gradient(180deg, #cbdcb4, #B5C99A); color:#22311a; text-shadow:0 1px 1px rgba(255,255,255,.3);}  /* sabio  */
        .disk-c4 { background: linear-gradient(180deg, #9a9b9e, #88898C); color:#1e1f20; text-shadow:0 1px 1px rgba(255,255,255,.25);} /* acero  */
        .disk-c5 { background: linear-gradient(180deg, #f5d4a3, #EFC18B); color:#3b2a10; text-shadow:0 1px 1px rgba(255,255,255,.35);} /* oro    */
        .disk-c6 { background: linear-gradient(180deg, #b21a61, #96134B); }                       /* vino compl. */
        .disk-c7 { background: linear-gradient(180deg, #585858, #454545); }                       /* gris medio  */

        /* ---------- Botones / acentos ---------- */
        .btn-executive {
            background: linear-gradient(180deg, var(--c-gold), var(--c-bronze));
            border: none;
            color: #2a1e0a;
            font-weight: 600;
            letter-spacing: .5px;
        }
        .btn-executive:hover { filter: brightness(1.07); color: #2a1e0a; }
        .btn-outline-soft {
            border: 1px solid var(--border);
            color: var(--text);
            background: transparent;
        }
        .btn-outline-soft:hover {
            background: var(--surface-2);
            color: var(--text);
            border-color: var(--accent);
        }
        .btn-wine {
            background: linear-gradient(180deg, var(--c-wine2), var(--c-wine));
            border: none;
            color: #fbe8f0;
            font-weight: 600;
        }
        .btn-wine:hover { filter: brightness(1.08); color: #fff; }

        /* Botones de información (Objetivo / Instrucciones) */
        .btn-info-exec {
            border: 1px solid rgba(187,148,92,.55);
            color: var(--c-gold);
            background: rgba(187,148,92,.06);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: .78rem;
        }
        .btn-info-exec:hover {
            background: rgba(187,148,92,.16);
            color: var(--c-gold);
            border-color: var(--c-bronze);
        }

        /* ---------- Leaderboard ---------- */
        .table-hanoi {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text);
            color: var(--text);
        }
        .table-hanoi thead th {
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: .64rem;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border) !important;
            font-weight: 600;
            white-space: nowrap;
        }
        .table-hanoi td {
            border-color: rgba(255,255,255,.05) !important;
            font-variant-numeric: tabular-nums;
            vertical-align: middle;
            font-size: .85rem;
        }
        .table-hanoi tr.top-rank td { color: var(--c-gold); font-weight: 600; }

        .rank-badge {
            display: inline-block;
            min-width: 34px;
            padding: .2rem .45rem;
            border-radius: 6px;
            font-weight: 700;
            font-size: .72rem;
            letter-spacing: 1px;
            text-align: center;
        }
        .rank-AAA { background: rgba(239,193,139,.18); color: #efc18b; border: 1px solid rgba(239,193,139,.45); }
        .rank-AA  { background: rgba(181,201,154,.16); color: #cfe0b8; border: 1px solid rgba(181,201,154,.4); }
        .rank-A   { background: rgba(187,148,92,.16);  color: #d8b07a; border: 1px solid rgba(187,148,92,.4); }
        .rank-B   { background: rgba(136,137,140,.16); color: #c3c4c6; border: 1px solid rgba(136,137,140,.35); }

        /* ---------- Modales ---------- */
        .modal-content.hanoi-modal {
            background: linear-gradient(180deg, var(--surface-2), var(--surface));
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 16px;
        }
        .hanoi-modal .modal-header { border-bottom: 1px solid var(--border); }
        .hanoi-modal .modal-footer { border-top: 1px solid var(--border); }

        .victory-phrase {
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 700;
            letter-spacing: .5px;
            background: linear-gradient(90deg, var(--c-gold), var(--c-bronze), var(--c-sage));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .victory-quote {
            font-style: italic;
            color: var(--c-sage);
            font-size: 1.02rem;
            max-width: 30rem;
            margin: 0 auto;
            line-height: 1.5;
        }
        .victory-metric { text-align: center; }
        .victory-metric .v { font-size: 1.7rem; font-weight: 700; color: var(--c-gold); }
        .victory-metric .l { font-size: .68rem; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); }

        /* ---------- Contenido informativo (Objetivo / Instrucciones) ---------- */
        .info-section { margin-bottom: 1.4rem; }
        .info-section:last-child { margin-bottom: 0; }
        .info-title {
            font-size: 1.02rem;
            font-weight: 700;
            color: var(--c-gold);
            margin-bottom: .35rem;
            letter-spacing: .3px;
        }
        .info-intro {
            color: var(--text-muted);
            font-size: .92rem;
            margin-bottom: .6rem;
        }
        .info-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        .info-list li {
            position: relative;
            padding-left: 1.2rem;
            margin-bottom: .5rem;
            font-size: .92rem;
            line-height: 1.45;
            color: var(--text);
        }
        .info-list li::before {
            content: "";
            position: absolute;
            left: 0; top: .55em;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--c-bronze);
        }
        .info-list li strong { color: var(--c-sage); }
        .info-divider {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 1.1rem 0;
            opacity: .8;
        }

        /* Selectores (discos / cronómetro) */
        .pick-group .btn-check:checked + .btn {
            background: linear-gradient(180deg, var(--c-gold), var(--c-bronze));
            color: #2a1e0a;
            border-color: var(--c-bronze);
        }
        .setup-block-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            margin-bottom: .6rem;
        }

        .text-muted-exec { color: var(--text-muted) !important; }

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
            Portal de <span class="accent">Optimización</span> Cognitiva
        </h1>
        <p class="hanoi-subtitle mb-3"><?= htmlspecialchars($config['subtitulo']) ?></p>

        <!-- Accesos informativos -->
        <div class="d-flex justify-content-center gap-2 mb-3">
            <button class="btn btn-info-exec px-3" data-bs-toggle="modal" data-bs-target="#objetivoModal">
                Objetivo
            </button>
            <button class="btn btn-info-exec px-3" data-bs-toggle="modal" data-bs-target="#instruccionesModal">
                Instrucciones
            </button>
        </div>

        <hr class="hanoi-divider w-50 mx-auto">
    </header>

    <div class="row g-4 justify-content-center">

        <!-- ============ COLUMNA PRINCIPAL: JUEGO ============ -->
        <div class="col-12 col-xl-8">

            <!-- Pantalla de configuración (menú inicial) -->
            <section id="setupScreen" class="hanoi-panel p-4 p-lg-5 text-center">
                <h2 class="h5 mb-2">Configura tu sesión</h2>
                <p class="text-muted-exec mb-4">
                    Selecciona el número de discos. A mayor cantidad, mayor reto cognitivo.
                </p>

                <!-- Número de discos -->
                <div class="setup-block-label">Número de discos</div>
                <div class="pick-group d-flex flex-wrap justify-content-center gap-2 mb-4" id="diskPicker">
                    <!-- Generado dinámicamente por JS según rango PHP -->
                </div>

                <!-- Modo de cronómetro -->
                <div class="setup-block-label">Cronómetro</div>
                <div class="pick-group d-flex flex-wrap justify-content-center gap-2 mb-4" id="timerPicker">
                    <input type="radio" class="btn-check" name="timerMode" id="timerOn" value="on" checked>
                    <label class="btn btn-outline-soft px-4" for="timerOn">Con cronómetro</label>
                    <input type="radio" class="btn-check" name="timerMode" id="timerOff" value="off">
                    <label class="btn btn-outline-soft px-4" for="timerOff">Sin cronómetro</label>
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
                    <div class="col-6 col-md-3" id="mTimeCard">
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
                                <th>Tiempo</th>
                                <th>Discos</th>
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

<!-- ============ MODAL: Objetivo ============ -->
<div class="modal fade" id="objetivoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content hanoi-modal">
            <div class="modal-header">
                <h5 class="modal-title">Objetivo del ejercicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">

                <div class="info-section">
                    <div class="info-title">👔 Ángulo Ejecutivo · Toma de decisiones y estrategia</div>
                    <p class="info-intro">
                        Desde la perspectiva de la alta dirección, el objetivo de la Torre de Hanói es
                        entrenar la planificación estratégica de largo plazo y la optimización de recursos.
                    </p>
                    <ul class="info-list">
                        <li><strong>Pensar a futuro:</strong> obliga a visualizar el escenario final antes de ejecutar el primer movimiento.</li>
                        <li><strong>Gestión de crisis:</strong> enseña a descomponer un problema macro en subtareas manejables.</li>
                        <li><strong>Eficiencia:</strong> el fin ejecutivo es alcanzar la meta con el menor costo operativo (menos movimientos).</li>
                    </ul>
                </div>

                <hr class="info-divider">

                <div class="info-section">
                    <div class="info-title">🧠 Ángulo Psicológico · Cognición y resistencia</div>
                    <p class="info-intro">
                        En psicología, el juego mide y desarrolla las funciones ejecutivas y la tolerancia a la frustración.
                    </p>
                    <ul class="info-list">
                        <li><strong>Control de impulsos:</strong> frenar la respuesta automática para evaluar las consecuencias lógicas de cada acción.</li>
                        <li><strong>Memoria de trabajo:</strong> mantener activo el plan mental mientras cambias las piezas de lugar.</li>
                        <li><strong>Persistencia:</strong> desarrolla la resiliencia mental ante bloqueos o errores en la estrategia.</li>
                    </ul>
                </div>

                <hr class="info-divider">

                <div class="info-section">
                    <div class="info-title">⚡ Ángulo Neuronal · Plasticidad y conectividad</div>
                    <p class="info-intro">
                        A nivel cerebral, el objetivo es estimular la activación y la plasticidad de la corteza prefrontal.
                    </p>
                    <ul class="info-list">
                        <li><strong>Flexibilidad sináptica:</strong> desafía al cerebro a crear nuevas rutas neuronales para resolver problemas espaciales.</li>
                        <li><strong>Coordinación hemisférica:</strong> conecta el hemisferio izquierdo (lógica y secuencias) con el derecho (visión espacial).</li>
                        <li><strong>Eficiencia cerebral:</strong> entrena al cerebro para consumir menos energía al resolver tareas complejas repetitivas.</li>
                    </ul>
                </div>

                <hr class="info-divider">

                <div class="info-section">
                    <div class="info-title">💼 Desempeño en el trabajo · Productividad</div>
                    <p class="info-intro">
                        Llevado al entorno laboral, el objetivo de este acertijo es pulir la metodología de trabajo y la productividad.
                    </p>
                    <ul class="info-list">
                        <li><strong>Priorización:</strong> identificar qué "disco" (tarea) debe moverse primero para no bloquear los proyectos grandes.</li>
                        <li><strong>Pensamiento ágil:</strong> adaptarse rápidamente cuando una variable cambia y el plan original falla.</li>
                        <li><strong>Reducción de retrabajo:</strong> al entrenar la mente a no dar pasos en falso, disminuyen los errores en los procesos diarios de la empresa.</li>
                    </ul>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-executive px-4" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<!-- ============ MODAL: Instrucciones ============ -->
<div class="modal fade" id="instruccionesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content hanoi-modal">
            <div class="modal-header">
                <h5 class="modal-title">Instrucciones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="info-section">
                    <div class="info-title">Reglas fundamentales y objetivo del proceso</div>
                    <ul class="info-list">
                        <li><strong>Configuración inicial y meta:</strong> el juego inicia con la totalidad de los discos apilados en orden decreciente en la primera varilla (origen). El objetivo final es reconstruir la torre idéntica —manteniendo exactamente la misma posición, estructura y orden piramidal— trasladándola por completo a la tercera varilla (destino).</li>
                        <li><strong>Unicidad de movimiento:</strong> se permite desplazar únicamente un disco a la vez por cada transición. Está estrictamente prohibido mover bloques de piezas de forma simultánea.</li>
                        <li><strong>Mecánica de traslado:</strong> cada acción válida consiste en tomar exclusivamente el disco superior que se encuentre libre en cualquiera de las varillas y colocarlo en la cúspide de otra varilla (ya sea la de destino o la auxiliar).</li>
                        <li><strong>Restricción de escala:</strong> nunca se puede colocar un disco de mayor diámetro encima de uno de menor diámetro en ninguna de las varillas durante el transcurso del juego. Una pieza grande jamás debe bloquear o apoyarse sobre una más pequeña.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-executive px-4" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
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
                    <button class="btn btn-wine px-4" id="btnConfirmReset">Sí, reiniciar</button>
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
                <p class="victory-phrase mb-3">Ganaste ¿te sientes mejor?</p>

                <!-- Frase aleatoria de cierre psicológico -->
                <p class="victory-quote mb-4" id="vQuote"></p>

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

    const LS_KEY = "hanoi_leaderboard_v2";  // v2: incluye tiempo y discos.

    /* Frases de cierre psicológico (se muestra una al azar al ganar). */
    const VICTORY_QUOTES = [
        "Controla lo controlable. El orden de la mente dicta el orden del entorno.",
        "La eficiencia no es hacer más cosas, sino hacerlas con los movimientos exactos.",
        "Un paso a la vez, bajo una estrategia clara, resuelve el problema más complejo.",
        "La paciencia y la estructura vencen cualquier saturación operativa.",
        "Despejar la mente es el primer paso para tomar una decisión correcta.",
        "La mente centrada es la herramienta más poderosa del servidor público.",
    ];

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
        useTimer:  true,           // Mostrar cronómetro visible.
        startTime: null,           // Marca de inicio (primer movimiento).
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
        mTimeCard:   document.getElementById("mTimeCard"),
        btnReset:    document.getElementById("btnReset"),
        leaderBody:  document.getElementById("leaderBody"),
        leaderEmpty: document.getElementById("leaderEmpty"),
        todayLabel:  document.getElementById("todayLabel"),
        // Victoria
        vQuote:   document.getElementById("vQuote"),
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

    // Formatea una duración en segundos -> MM:SS (o HH:MM:SS si supera la hora).
    function formatDuration(totalSeconds) {
        const p = (x) => String(x).padStart(2, "0");
        const h = Math.floor(totalSeconds / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);
        const s = totalSeconds % 60;
        return h > 0 ? `${p(h)}:${p(m)}:${p(s)}` : `${p(m)}:${p(s)}`;
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
                <td>${en.time}</td>
                <td>${en.duration || "—"}</td>
                <td>${en.disks || "—"}</td>`;
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

            // --- Eventos de torre: clic y destinos de arrastre ---
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
                disk.className = "disk disk-c" + ((size - 1) % 8) + (isTop ? " is-top" : "");
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
            flashInvalid(to);   // Feedback sutil de movimiento inválido.
            clearSelection();
            return;
        }

        // El cronómetro/medición arranca con el PRIMER movimiento válido.
        if (state.startTime === null) startTimer();

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
     *  - La medición arranca con el primer movimiento.
     *  - El cronómetro visible solo se actualiza si useTimer = true.
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
        if (!state.useTimer) return;   // Sin cronómetro: no se actualiza la vista.
        el.mTime.textContent = formatDuration(elapsedSeconds());
    }

    /* ===============================================================
     *  CICLO DE JUEGO
     * ============================================================= */

    function readTimerPreference() {
        const on = document.getElementById("timerOn");
        return on ? on.checked : true;
    }

    function startGame() {
        state.useTimer = readTimerPreference();

        state.pegs = [[], [], []];
        // Torre origen: discos de mayor (fondo) a menor (cima).
        for (let s = state.numDisks; s >= 1; s--) state.pegs[0].push(s);

        state.moves = 0;
        state.ideal = idealMoves(state.numDisks);
        state.selected = null;
        state.active = true;
        state.startTime = null;   // Aún no arranca; lo hará en el 1er movimiento.
        stopTimer();

        el.mUser.textContent = "0";
        el.mIdeal.textContent = state.ideal;
        el.mScore.textContent = "1000";
        el.mTime.textContent = "00:00";

        // Mostrar u ocultar la tarjeta del cronómetro según preferencia.
        el.mTimeCard.classList.toggle("d-none", !state.useTimer);

        buildBoard();

        el.setupScreen.classList.add("d-none");
        el.gameScreen.classList.remove("d-none");
    }

    function backToMenu() {
        state.active = false;
        stopTimer();
        state.startTime = null;
        clearSelection();
        el.gameScreen.classList.add("d-none");
        el.setupScreen.classList.remove("d-none");
    }

    function checkVictory() {
        // Victoria: todos los discos en la torre Destino (índice 2).
        if (state.pegs[2].length !== state.numDisks) return;

        state.active = false;
        stopTimer();

        const score    = computeScore(state.moves, state.ideal);
        const rank     = computeRank(state.moves, state.ideal);
        const clock    = nowTime();                       // Hora del logro (HH:MM:SS).
        const duration = formatDuration(elapsedSeconds()); // Tiempo de resolución.

        // Persistir en la tabla diaria local (incluye tiempo y discos).
        addLeaderboardEntry({
            moves:    state.moves,
            score:    score,
            rank:     rank,
            time:     clock,
            duration: duration,
            disks:    state.numDisks,
        });

        // Frase aleatoria de cierre.
        el.vQuote.textContent =
            VICTORY_QUOTES[Math.floor(Math.random() * VICTORY_QUOTES.length)];

        // Métricas del modal de victoria.
        el.vMoves.textContent = state.moves;
        el.vScore.textContent = score;
        el.vRank.textContent  = rank;

        let cierre;
        if (rank === "AAA")      cierre = "Eficiencia perfecta. Mente despejada y precisa.";
        else if (rank === "AA")  cierre = "Excelente control. Casi impecable.";
        else if (rank === "A")   cierre = "Buen desempeño. La calma rinde frutos.";
        else                     cierre = "Resuelto. Respira: lo importante es haber terminado.";

        el.vSummary.textContent =
            `Discos: ${state.numDisks} · Ideales: ${state.ideal} · Tus movimientos: ${state.moves} · Tiempo: ${duration}. ${cierre}`;

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

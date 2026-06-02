<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cardioprenatal')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml" sizes="any">

    @vite(['resources/js/app.js'])

    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        :root {
            --primary:      #7f0c1a;
            --accent:       #c0392b;
            --accent-mid:   #e74c3c;
            --accent-light: #fdf0f0;
            --success:      #27ae60;
            --text:         #1c1a1a;
            --muted:        #8a6f6f;
            --border:       #f0d5d5;
            --surface:      #ffffff;
            --bg:           #faf4f4;
            --nav-bg:       #7f0c1a;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            color: var(--text);
            position: relative;
            overflow-x: clip;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 55% at 100% 0%, rgba(192, 57, 43, 0.08) 0%, transparent 55%),
                radial-gradient(ellipse 55% 45% at 0% 100%, rgba(127, 12, 26, 0.06) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        body > header,
        body > main,
        body > footer.app-site-footer {
            position: relative;
            z-index: 1;
        }

        /* ── HEADER ── */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, #a01020 100%);
            box-shadow: 0 2px 16px rgba(127,12,26,.25);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 max(16px, env(safe-area-inset-right)) 0 max(16px, env(safe-area-inset-left));
            min-height: 56px;
            height: auto;
            padding-top: max(0px, env(safe-area-inset-top));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        /* Logo */
        .header-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 0;
            flex: 1 1 auto;
        }

        .header-logo-icon {
            min-width: 58px;
            height: 38px;
            padding: 0 10px;
            background: rgba(255,255,255,.18);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,.2);
        }

        .header-logo-glyphs {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .header-logo-icon svg {
            flex-shrink: 0;
            color: #fff;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.12));
        }

        .header-logo-icon .heart-brand {
            width: 21px;
            height: 21px;
        }

        .header-logo-icon .icon-baby-brand {
            width: 20px;
            height: 20px;
            opacity: 0.98;
        }

        /* Pulso suave — eco do “batimento” (tema cardiológico / CHD) */
        .heart-brand {
            transform-origin: center;
            animation: heartPulse 2.4s ease-in-out infinite;
        }

        @keyframes heartPulse {
            0%, 100% { transform: scale(1); }
            12% { transform: scale(1.12); }
            24% { transform: scale(1); }
            36% { transform: scale(1.08); }
            48% { transform: scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .heart-brand { animation: none; }
        }

        .header-logo-text {
            font-family: 'DM Serif Display', serif;
            font-size: 21px;
            color: #fff;
            letter-spacing: -0.02em;
            font-weight: 400;
        }

        .header-logo-tagline {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,255,255,.72);
            margin-top: 2px;
            line-height: 1.2;
        }

        .header-logo-text-wrap {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }

        /* Nav desktop */
        .nav-desktop {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: rgba(255,255,255,.8);
            text-decoration: none;
            transition: background .18s, color .18s;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,.15);
            color: #fff;
        }

        .nav-link svg { width: 16px; height: 16px; }
        .nav-icon-baby { flex-shrink: 0; }

        /* Separador vertical */
        .nav-sep {
            width: 1px;
            height: 20px;
            background: rgba(255,255,255,.2);
            margin: 0 6px;
        }

        /* Botão sair */
        .btn-logout {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: rgba(255,255,255,.8);
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            cursor: pointer;
            transition: background .18s, color .18s;
            font-family: 'DM Sans', sans-serif;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,.18);
            color: #fff;
        }

        .btn-logout svg { width: 15px; height: 15px; }

        /* Botão mobile */
        .btn-mobile-menu {
            display: none;
            background: rgba(255,255,255,.12);
            border: none;
            border-radius: 8px;
            padding: 8px;
            cursor: pointer;
            color: #fff;
        }

        .btn-mobile-menu svg { width: 22px; height: 22px; display: block; }

        /* Menu mobile */
        .nav-mobile {
            display: none;
            background: var(--primary);
            border-top: 1px solid rgba(255,255,255,.1);
            padding: 12px 16px 16px;
            flex-direction: column;
            gap: 4px;
        }

        .nav-mobile.open {
            display: flex;
            max-height: min(70vh, calc(100dvh - 56px));
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .nav-mobile .nav-link { justify-content: flex-start; }

        .nav-mobile-sep {
            height: 1px;
            background: rgba(255,255,255,.12);
            margin: 8px 0;
        }

        /* ── MAIN ── */
        main {
            flex: 1;
            max-width: 1280px;
            width: 100%;
            min-width: 0;
            margin: 0 auto;
            padding: 32px max(16px, env(safe-area-inset-right)) 32px max(16px, env(safe-area-inset-left));
        }

        /* ── FOOTER ── */
        footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 14px max(16px, env(safe-area-inset-right)) max(14px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 6px;
            row-gap: 8px;
        }

        .footer-brand-icons {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 2px;
        }

        .footer-brand-icons svg {
            width: 13px;
            height: 13px;
            color: var(--accent);
            flex-shrink: 0;
        }

        /* Menu compacto a partir de ~tablet — evita links espremidos no header */
        @media (max-width: 991px) {
            .nav-desktop { display: none; }
            .btn-mobile-menu { display: flex; flex-shrink: 0; }
        }

        @media (max-width: 768px) {
            main { padding: 20px max(14px, env(safe-area-inset-right)) 24px max(14px, env(safe-area-inset-left)); }
        }

        @media (max-width: 420px) {
            .header-logo-text { font-size: 17px; }
            .header-logo-tagline { font-size: 9px; letter-spacing: 0.08em; }
            .header-logo-icon { min-width: 52px; height: 34px; padding: 0 8px; }
        }

        /* ── COMPONENTES GLOBAIS DE UI ── */
        .page-header { margin-bottom: 32px; animation: fadeIn 0.5s ease-out; }
        .page-title { font-family: 'DM Serif Display', serif; font-size: clamp(1.35rem, 4vw + 0.5rem, 2rem); color: var(--primary); line-height: 1.15; word-break: break-word; }
        .page-subtitle { font-size: clamp(13px, 2.5vw, 15px); color: var(--muted); margin-top: 6px; line-height: 1.45; }

        /* Linha título + ações (dashboard, listas) */
        .card-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .card-toolbar .card-title { margin: 0; flex: 1 1 200px; min-width: 0; }
        @media (max-width: 560px) {
            .card-toolbar { flex-direction: column; align-items: stretch; }
            .card-toolbar .btn-analisar-wrap,
            .card-toolbar .btn-primary-custom { width: 100%; justify-content: center; }
        }

        .main-card {
            background: var(--surface);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 40px rgba(127,12,26,0.1);
            border: 1px solid var(--border);
            margin-bottom: 30px;
            animation: fadeIn 0.6s ease-out;
            max-width: 100%;
            min-width: 0;
        }

        @media (max-width: 640px) {
            .main-card { padding: 20px 16px; border-radius: 18px; }
        }

        .card-title { font-family: 'DM Serif Display', serif; font-size: clamp(1.05rem, 2.5vw + 0.6rem, 1.375rem); color: var(--primary); display: flex; align-items: center; flex-wrap: wrap; gap: 10px; min-width: 0; }

        /* ── Tabelas (layout moderno) ── */
        .table-container {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 20px;
            background: var(--surface);
            box-shadow:
                0 12px 40px rgba(127, 12, 26, 0.07),
                0 0 0 1px rgba(127, 12, 26, 0.06);
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .table-container--flush { margin-top: 0; }

        .table-scroll-hint {
            display: none;
            font-size: 12px;
            color: var(--muted);
            margin: 0 0 10px;
            text-align: center;
            line-height: 1.4;
        }

        @media (max-width: 1024px) {
            .table-scroll-hint { display: block; }
        }

        /* Largura pelo conteúdo: evita esmagar colunas (CPF/telefone quebrando no meio).
           O .table-container fornece rolagem horizontal. */
        table.data-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: transparent;
        }

        table.data-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(180deg, #fff9f9 0%, #f8eaea 100%);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 16px 20px;
            text-align: left;
            border-bottom: 2px solid rgba(127, 12, 26, 0.14);
            white-space: nowrap;
        }

        table.data-table tbody td {
            padding: 16px 20px;
            font-size: 14px;
            color: var(--text);
            border-bottom: 1px solid rgba(240, 213, 213, 0.55);
            vertical-align: middle;
            line-height: 1.45;
            background: var(--surface);
        }

        table.data-table tbody tr {
            transition: background-color 0.15s ease;
        }

        table.data-table tbody tr:nth-child(even) td {
            background: rgba(253, 240, 240, 0.4);
        }

        table.data-table tbody tr:hover td {
            background: rgba(127, 12, 26, 0.055);
        }

        table.data-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.data-table .td-num {
            font-variant-numeric: tabular-nums;
            text-align: center;
        }

        /* Coluna de ações fixa à direita ao rolar horizontalmente */
        table.data-table thead th.td-actions,
        table.data-table tbody td.td-actions {
            position: sticky;
            right: 0;
        }

        table.data-table thead th.td-actions {
            z-index: 4;
            background: linear-gradient(180deg, #fff9f9 0%, #f8eaea 100%);
            box-shadow: -10px 0 18px rgba(127, 12, 26, 0.07);
        }

        table.data-table tbody td.td-actions {
            z-index: 2;
            text-align: right;
            white-space: nowrap;
            min-width: 220px;
            vertical-align: middle;
            background: var(--surface);
            box-shadow: -8px 0 14px rgba(127, 12, 26, 0.06);
        }

        table.data-table tbody tr:nth-child(even) td.td-actions {
            background: rgba(253, 240, 240, 0.95);
        }

        table.data-table tbody tr:hover td.td-actions {
            background: rgba(127, 12, 26, 0.09);
        }

        table.data-table .td-actions-inner {
            display: inline-flex;
            flex-wrap: nowrap;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        table.data-table th.td-nowrap,
        table.data-table td.td-nowrap {
            white-space: nowrap;
        }

        table.data-table th.td-cell-nome,
        table.data-table td.td-cell-nome {
            white-space: normal;
            word-break: break-word;
            max-width: min(12rem, 42vw);
            min-width: 7rem;
        }

        @media (min-width: 900px) {
            table.data-table th.td-cell-nome,
            table.data-table td.td-cell-nome {
                max-width: 18rem;
            }
        }

        table.data-table th.td-num--narrow,
        table.data-table td.td-num--narrow {
            white-space: nowrap;
        }

        table.data-table tbody tr.data-table-empty td {
            text-align: center;
            color: var(--muted);
            padding: 40px 24px;
            font-size: 15px;
            background: var(--surface) !important;
        }

        .btn-table {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }

        .btn-table:hover { transform: translateY(-1px); }

        .btn-table--primary {
            background: linear-gradient(135deg, var(--primary), var(--accent-mid));
            color: #fff;
            box-shadow: 0 2px 12px rgba(192, 57, 43, 0.28);
        }

        .btn-table--secondary {
            background: rgba(192, 57, 43, 0.11);
            color: var(--primary);
        }

        .btn-table--danger {
            background: rgba(192, 57, 43, 0.16);
            color: var(--primary);
        }

        .table-pagination {
            margin-top: 24px;
            padding-top: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        /* Paginação custom (vendor/pagination/cardioprenatal) — evita sobreposição do template Tailwind (-ml-px) */
        .cp-pagination {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            width: 100%;
            min-width: 0;
        }

        @media (min-width: 640px) {
            .cp-pagination {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }
        }

        .cp-pagination__summary {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            text-align: center;
            line-height: 1.45;
        }

        .cp-pagination__summary strong {
            color: var(--text);
            font-weight: 600;
        }

        .cp-pagination__list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .cp-pagination__item a,
        .cp-pagination__item--active span,
        .cp-pagination__item--disabled span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
            min-height: 2.5rem;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--primary);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            font-variant-numeric: tabular-nums;
            box-sizing: border-box;
        }

        .cp-pagination__item a:hover {
            background: rgba(253, 240, 240, 0.85);
            border-color: rgba(192, 57, 43, 0.35);
        }

        .cp-pagination__item--active span {
            background: linear-gradient(135deg, var(--primary), var(--accent-mid));
            color: #fff;
            border-color: transparent;
        }

        .cp-pagination__item--disabled span {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }

        .cp-pagination__item--ellipsis span {
            min-width: auto;
            border: none;
            background: transparent;
            color: var(--muted);
            font-weight: 500;
            padding: 0 4px;
        }

        /* ── Formulários longos (consultas etc.) ── */
        .form-page-grid {
            display: grid;
            gap: 22px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .form-page-grid--2 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (min-width: 768px) and (max-width: 1099px) {
            .form-page-grid--3 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (min-width: 1100px) {
            .form-page-grid--3 { grid-template-columns: repeat(3, 1fr); }
        }

        .form-section {
            background: rgba(253, 240, 240, 0.42);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 22px 24px;
        }

        @media (max-width: 640px) {
            .form-section { padding: 16px 14px; border-radius: 14px; }
        }

        .form-section--wide {
            grid-column: 1 / -1;
        }

        @media (min-width: 1100px) {
            .form-page-grid--3 .form-section--span-2 {
                grid-column: span 2;
            }
        }

        .form-section-title {
            font-family: 'DM Serif Display', serif;
            font-size: 18px;
            color: var(--primary);
            margin: 0 0 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .form-field { margin-bottom: 16px; }
        .form-field:last-child { margin-bottom: 0; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 15px;
            background: var(--surface);
            color: var(--text);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: rgba(192, 57, 43, 0.5);
            box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.12);
        }

        .form-field-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 520px) {
            .form-field-grid--2 { grid-template-columns: repeat(2, 1fr); }
        }

        .form-alert-error {
            background: var(--accent-light);
            border: 1px solid var(--border);
            color: var(--primary);
            padding: 16px 18px;
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .form-alert-error ul {
            margin: 10px 0 0;
            padding-left: 1.15rem;
            line-height: 1.55;
            font-size: 14px;
        }

        .form-actions {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 480px) {
            .form-actions { flex-direction: column-reverse; align-items: stretch; }
            .form-actions > * { width: 100%; justify-content: center; }
            .form-actions-end { width: 100%; justify-content: stretch; }
            .form-actions-end .btn-primary-custom,
            .form-actions-end .btn-secondary-outline { flex: 1; justify-content: center; }
        }

        .form-actions-end {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            align-items: center;
        }

        .btn-secondary-outline {
            display: inline-flex;
            align-items: center;
            padding: 12px 22px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-secondary-outline:hover {
            background: rgba(253, 240, 240, 0.6);
        }

        /* Leitura de consulta (ficha da gestante) */
        .gestante-form-actions {
            align-items: center;
        }

        @media (max-width: 480px) {
            .gestante-form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .gestante-form-actions .btn-primary-custom,
            .gestante-form-actions a {
                width: 100%;
                justify-content: center;
            }
        }

        .gestante-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        @media (max-width: 520px) {
            .gestante-header-actions {
                width: 100%;
                justify-content: stretch;
            }
            .gestante-header-actions > a {
                flex: 1 1 calc(50% - 5px);
                justify-content: center;
                min-width: 0;
            }
        }

        @media (max-width: 380px) {
            .gestante-header-actions { flex-direction: column; }
            .gestante-header-actions > a { flex: none; width: 100%; }
        }

        .consulta-resumo-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 4px;
        }

        @media (max-width: 560px) {
            .consulta-resumo-head {
                flex-direction: column;
                align-items: stretch;
            }
            .consulta-resumo-head .btn-table { align-self: flex-start; }
        }

        .consulta-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .consulta-pill--ok {
            background: rgba(39, 174, 96, 0.14);
            color: #1e8449;
        }

        .consulta-pill--alert {
            background: rgba(192, 57, 43, 0.12);
            color: var(--primary);
        }

        .consulta-valor {
            margin: 4px 0 0;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.45;
        }

        .consulta-chd-box {
            margin-top: 12px;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(253, 240, 240, 0.65);
            border: 1px solid var(--border);
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            table.data-table thead th,
            table.data-table tbody td {
                padding: 12px 14px;
                font-size: 13px;
            }

            table.data-table thead th {
                font-size: 10px;
            }

            table.data-table .td-actions-inner {
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            table.data-table thead th,
            table.data-table tbody td {
                padding: 10px 11px;
                font-size: 12px;
            }

            table.data-table thead th {
                font-size: 9px;
                letter-spacing: 0.05em;
            }

            .btn-table {
                padding: 8px 10px;
                font-size: 12px;
            }
        }

        main .main-card img,
        .grafico-item img {
            max-width: 100%;
            height: auto;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--accent-mid));
            color: white;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary-custom:hover { opacity: 0.9; transform: scale(1.02); }

        /* Modal genérico (gestantes etc.)
           Importante: não usar display:flex no estado fechado — conflita com .hidden do Tailwind. */
        .app-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: max(12px, env(safe-area-inset-top)) max(12px, env(safe-area-inset-right)) max(12px, env(safe-area-inset-bottom)) max(12px, env(safe-area-inset-left));
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .app-modal-overlay.is-open {
            display: flex;
        }
        .app-modal-panel {
            max-width: 400px;
            width: 100%;
            margin: auto;
        }

        .app-modal-panel--lg {
            max-width: min(560px, calc(100vw - 32px));
        }

        .app-modal-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 12px;
        }
        @media (max-width: 420px) {
            .app-modal-actions { flex-direction: column; align-items: stretch; }
            .app-modal-actions button,
            .app-modal-actions .btn-primary-custom { width: 100%; justify-content: center; }
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="header-inner">
            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="header-logo" title="Cardioprenatal — rastreio de cardiopatias congênitas">
                <div class="header-logo-icon" aria-hidden="true" title="Coração e bebê — cuidado fetal">
                    <div class="header-logo-glyphs">
                        <svg class="heart-brand" viewBox="0 0 24 24" fill="currentColor" focusable="false">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <svg class="icon-baby-brand" viewBox="0 0 24 24" fill="currentColor" focusable="false">
                            <path d="M12 4.75a3.15 3.15 0 1 0 0 6.3 3.15 3.15 0 0 0 0-6.3z"/>
                            <path d="M12 12.2c-3.35 0-6.1 2.15-6.1 4.8V21h12.2v-4c0-2.65-2.75-4.8-6.1-4.8z"/>
                        </svg>
                    </div>
                </div>
                <span class="header-logo-text-wrap">
                    <span class="header-logo-text">Cardioprenatal</span>
                    <span class="header-logo-tagline">Cardiopatias congênitas · pré-natal</span>
                </span>
            </a>

            <!-- Nav desktop -->
            <nav class="nav-desktop">
                <a href="{{ route('dashboard') }}" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('gestantes.index') }}" class="nav-link">
                    <svg class="nav-icon-baby" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="7.5" r="3.2"/>
                        <circle cx="9.5" cy="12" r="0.65" fill="currentColor" stroke="none"/>
                        <circle cx="14.5" cy="12" r="0.65" fill="currentColor" stroke="none"/>
                        <path d="M10 15.5c.6.9 1.4 1.3 2 1.3s1.4-.4 2-1.3"/>
                        <path d="M6 20c0-2.8 2.6-5 6-5s6 2.2 6 5"/>
                    </svg>
                    Gestantes
                </a>
                <a href="{{ route('historico-whatsapp.index') }}" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Histórico WhatsApp
                </a>
                <a href="{{ route('consultas.import') }}" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Importar CSV
                </a>
                <div class="nav-sep"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Sair
                    </button>
                </form>
            </nav>

            <!-- Botão mobile -->
            <button type="button" class="btn-mobile-menu" id="menuButton" aria-label="Abrir menu" aria-expanded="false" aria-controls="mobileMenu">
                <svg id="menuIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Nav mobile -->
        <nav class="nav-mobile" id="mobileMenu" role="navigation" aria-label="Menu principal">
            <a href="{{ route('dashboard') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>
            <a href="{{ route('gestantes.index') }}" class="nav-link">
                <svg class="nav-icon-baby" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true">
                    <circle cx="12" cy="7.5" r="3.2"/>
                    <circle cx="9.5" cy="12" r="0.65" fill="currentColor" stroke="none"/>
                    <circle cx="14.5" cy="12" r="0.65" fill="currentColor" stroke="none"/>
                    <path d="M10 15.5c.6.9 1.4 1.3 2 1.3s1.4-.4 2-1.3"/>
                    <path d="M6 20c0-2.8 2.6-5 6-5s6 2.2 6 5"/>
                </svg>
                Gestantes
            </a>
            <a href="{{ route('historico-whatsapp.index') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Histórico WhatsApp
            </a>
            <a href="{{ route('consultas.import') }}" class="nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Importar CSV
            </a>
            <div class="nav-mobile-sep"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" style="width:100%; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Sair
                </button>
            </form>
        </nav>
    </header>

    <!-- Conteúdo -->
    <main>
        @yield('content')
    </main>

    <footer class="app-site-footer">
        <span class="footer-brand-icons" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.75a3.15 3.15 0 1 0 0 6.3 3.15 3.15 0 0 0 0-6.3z"/><path d="M12 12.2c-3.35 0-6.1 2.15-6.1 4.8V21h12.2v-4c0-2.65-2.75-4.8-6.1-4.8z"/></svg>
        </span>
        <span>Cardioprenatal — cuidado materno-fetal e rastreio de CHD</span>
    </footer>

    <script>
        const menuButton = document.getElementById('menuButton');
        const mobileMenu = document.getElementById('mobileMenu');

        menuButton.addEventListener('click', () => {
            const open = mobileMenu.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            menuButton.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
        });

        mobileMenu.querySelectorAll('a.nav-link, .btn-logout').forEach((el) => {
            el.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-label', 'Abrir menu');
            });
        });

        // Marca o link ativo
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    </script>

    @stack('scripts')

</body>

</html>
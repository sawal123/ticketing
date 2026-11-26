@extends('frontend.index')

@section('content')
    <style>
        .search-page-container {
            background-color: var(--bg);
            min-height: 85vh;
            padding-top: 100px;
            padding-bottom: 80px;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        .card-img {
            position: relative;
            height: 180px;
            overflow: hidden;
            border-radius: 16px;
        }

        .img-bg {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
        }

        .empty-state-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 56px 28px;
            box-shadow: var(--card-base-shadow);
            text-align: center;
            max-width: 780px;
            margin: 20px auto 40px;
            position: relative;
            overflow: hidden;
            transition: background-color 0.25s, border-color 0.25s;
        }

        .empty-state-card::before {
            content: "";
            position: absolute;
            top: -120px;
            left: 50%;
            transform: translateX(-50%);
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(108, 92, 231, 0.12) 0%, transparent 70%);
            pointer-events: none;
            border-radius: 50%;
        }

        .empty-state-img-wrap {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }

        .empty-state-img {
            max-width: 380px;
            width: 100%;
            height: auto;
            max-height: 240px;
            object-fit: contain;
            filter: drop-shadow(0 10px 24px rgba(0, 0, 0, 0.08));
            transition: transform 0.3s ease;
        }

        .empty-state-img:hover {
            transform: scale(1.02);
        }

        .empty-state-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
            line-height: 1.25;
        }

        .empty-state-desc {
            font-size: 15px;
            color: var(--muted);
            max-width: 560px;
            margin: 0 auto 28px;
            line-height: 1.6;
        }

        .empty-state-search-form {
            max-width: 520px;
            margin: 0 auto 26px;
        }

        .empty-search-input-group {
            position: relative;
            display: flex;
            align-items: center;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 6px 8px 6px 20px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .empty-search-input-group:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.15);
        }

        .search-icon {
            width: 18px;
            height: 18px;
            color: var(--dim);
            flex-shrink: 0;
            margin-right: 12px;
        }

        .empty-search-input {
            flex: 1;
            border: none;
            background: transparent;
            color: var(--text);
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }

        .empty-search-input::placeholder {
            color: var(--dim);
        }

        .clear-search-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--surface3);
            color: var(--muted);
            margin-right: 8px;
            transition: 0.15s;
            text-decoration: none;
        }

        .clear-search-btn:hover {
            color: var(--text);
            background: var(--border);
        }

        .clear-search-btn svg {
            width: 14px;
            height: 14px;
        }

        .empty-search-submit {
            background: linear-gradient(135deg, #6c5ce7 0%, #a78bfa 100%);
            color: #ffffff;
            border: none;
            padding: 9px 24px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
        }

        .empty-search-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .empty-state-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .btn-empty-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent);
            color: #ffffff !important;
            padding: 10px 22px;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
            box-shadow: 0 4px 14px rgba(108, 92, 231, 0.25);
        }

        .btn-empty-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 92, 231, 0.35);
        }

        .btn-empty-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            color: var(--text) !important;
            border: 1px solid var(--border2);
            padding: 10px 22px;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-empty-secondary:hover {
            border-color: var(--accent);
            color: var(--accent) !important;
            transform: translateY(-2px);
        }

        .btn-icon {
            width: 16px;
            height: 16px;
        }

        .empty-category-section {
            border-top: 1px dashed var(--border);
            padding-top: 24px;
            margin-top: 10px;
        }

        .category-heading {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 14px;
        }

        .category-chips-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
        }

        .category-chip-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: 100px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            transition: 0.2s;
        }

        .category-chip-pill:hover,
        .category-chip-pill.active {
            background: var(--surface2);
            border-color: var(--accent);
            color: var(--text);
            transform: translateY(-1px);
        }

        .chip-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            opacity: 0.7;
        }

        .search-header-bar {
            margin-bottom: 32px;
        }

        .search-header-bar h1 {
            font-size: 30px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .search-header-bar p {
            color: var(--muted);
            font-size: 14px;
        }
    </style>

    <div class="search-page-container">
        <div class="container">
            @if (session('deleteList'))
                <div class="alert alert-danger mb-4">
                    {{ session('deleteList') }}
                </div>
            @endif

            @include('frontend.page.post.post-menu')
        </div>
    </div>
@endsection

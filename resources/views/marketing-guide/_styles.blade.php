        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light: #a78bfa;
            --secondary: #0f172a;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            background-color: #ffffff;
            line-height: 1.6;
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            width: 0%;
            z-index: 999;
            transition: width 0.1s ease;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 72px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 100;
            padding-top: 3px;
        }

        .header-container {
            max-width: 100%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 24px;
            padding: 8px;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 72px;
            width: 280px;
            height: calc(100vh - 72px);
            background: var(--surface-alt);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 32px 0;
            z-index: 90;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 32px;
            padding: 0 24px;
        }

        .nav-section {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 0 12px 8px 12px;
        }

        .nav-link {
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            display: block;
        }

        .nav-link:hover {
            background-color: rgba(124, 58, 237, 0.1);
            color: var(--primary);
        }

        .nav-link.active {
            background-color: rgba(124, 58, 237, 0.15);
            color: var(--primary);
            font-weight: 600;
        }

        /* Main Content */
        .main-wrapper {
            display: flex;
            margin-top: 72px;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 0;
            background: white;
        }

        section {
            padding: 80px 64px;
            border-bottom: 1px solid var(--border);
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        section:nth-child(1) { animation-delay: 0.1s; }
        section:nth-child(2) { animation-delay: 0.2s; }
        section:nth-child(3) { animation-delay: 0.3s; }

        @@keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Typography */
        h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -1px;
            color: var(--text-primary);
        }

        h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        p {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .subtitle {
            font-size: 20px;
            color: var(--text-secondary);
            margin-bottom: 32px;
            font-weight: 500;
            line-height: 1.6;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            background: rgba(124, 58, 237, 0.1);
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        /* Button */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--surface-alt);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn-large {
            padding: 16px 40px;
            font-size: 16px;
        }

        /* Cards */
        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .card-description {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Grid */
        .grid {
            display: grid;
            gap: 24px;
            margin-bottom: 32px;
        }

        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        /* Workflow */
        .workflow {
            display: flex;
            flex-direction: column;
            gap: 24px;
            margin-bottom: 32px;
        }

        .workflow-step {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        .workflow-step::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 60px;
            width: 2px;
            height: 60px;
            background: var(--border);
        }

        .workflow-step:last-child::after {
            display: none;
        }

        .workflow-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(167, 139, 250, 0.1));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 24px;
            flex-shrink: 0;
            z-index: 1;
        }

        .workflow-content {
            flex: 1;
        }

        .workflow-content h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .workflow-content p {
            margin: 0;
            font-size: 14px;
        }

        /* Flow Diagram */
        .flow-diagram {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .flow-box {
            background: var(--surface-alt);
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            text-align: center;
            min-width: 140px;
        }

        .flow-arrow {
            font-size: 24px;
            color: var(--primary);
            flex-shrink: 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--surface-alt);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Placeholder */
        .placeholder {
            background: linear-gradient(135deg, var(--surface-alt) 0%, rgba(124, 58, 237, 0.05) 100%);
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 60px 40px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 32px;
        }

        .placeholder-icon {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--primary);
        }

        /* Ticket Examples */
        .ticket-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .ticket-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .ticket-type {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .ticket-price {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .ticket-quantity {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        /* QR Code Mockup */
        .qr-mockup {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 32px;
            max-width: 400px;
            margin: 0 auto 32px;
        }

        .qr-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .qr-event-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .qr-event-date {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .qr-code {
            width: 200px;
            height: 200px;
            background: var(--surface-alt);
            border: 2px solid var(--border);
            border-radius: 8px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: var(--primary);
        }

        .qr-footer {
            font-size: 13px;
            color: var(--text-secondary);
            text-align: center;
        }

        /* FAQ */
        .faq-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .faq-item {
            margin-bottom: 16px;
        }

        .faq-question {
            width: 100%;
            padding: 20px 24px;
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: 8px;
            text-align: left;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .faq-question.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            border-radius: 8px 8px 0 0;
        }

        .faq-icon {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .faq-question.active .faq-icon {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 8px 8px;
            transition: max-height 0.3s ease;
            padding: 0 24px;
        }

        .faq-answer.active {
            max-height: 500px;
            padding: 24px;
        }

        .faq-answer p {
            margin: 0;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 80px 64px;
            border-radius: 0;
        }

        .cta-section h2 {
            color: white;
            margin-bottom: 16px;
        }

        .cta-section p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 32px;
            font-size: 18px;
        }

        .btn-cta {
            background: white;
            color: var(--primary);
        }

        .btn-cta:hover {
            background: var(--surface-alt);
        }

        /* Drawer (Mobile Navigation) */
        .drawer {
            position: fixed;
            left: -280px;
            top: 72px;
            width: 280px;
            height: calc(100vh - 72px);
            background: var(--surface-alt);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 32px 0;
            z-index: 95;
            transition: left 0.3s ease;
        }

        .drawer.active {
            left: 0;
        }

        .drawer-overlay {
            position: fixed;
            top: 72px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 92;
        }

        .drawer-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive */
        @@media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }

            .main-content {
                margin-left: 240px;
            }

            section {
                padding: 60px 40px;
            }

            h1 {
                font-size: 40px;
            }

            h2 {
                font-size: 28px;
            }
        }

        @@media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .drawer-content {
                padding: 0 24px;
            }

            section {
                padding: 60px 24px;
            }

            h1 {
                font-size: 32px;
            }

            h2 {
                font-size: 24px;
            }

            .subtitle {
                font-size: 16px;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .flow-diagram {
                flex-direction: column;
            }

            .flow-arrow {
                transform: rotate(90deg);
                margin: -8px 0;
            }

            .cta-section {
                padding: 60px 24px;
            }

            .qr-mockup {
                max-width: 100%;
            }

            .faq-container {
                max-width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @@media (max-width: 480px) {
            h1 {
                font-size: 28px;
                margin-bottom: 12px;
            }

            h2 {
                font-size: 20px;
            }

            .subtitle {
                font-size: 15px;
                margin-bottom: 24px;
            }

            section {
                padding: 48px 16px;
            }

            .btn-large {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .faq-question {
                padding: 16px;
                font-size: 14px;
            }

            .faq-answer {
                padding: 0 16px;
            }

            .faq-answer.active {
                padding: 16px;
            }

            .card {
                padding: 16px;
            }

            .placeholder {
                padding: 40px 20px;
            }

            .placeholder-icon {
                font-size: 36px;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface-alt);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

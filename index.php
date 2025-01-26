<?php
session_start();
require_once __DIR__ . '/includes/check_maintenance.php';
checkMaintenance();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetboxCTF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #00ff00;
            --dark: #0a0a0a;
            --text: #ffffff;
            --text-muted: #888888;
            --terminal-bg: #141414;
            --border: #333333;
        }

        body {
            font-family: monospace;
            background-color: var(--dark);
            color: var(--text);
            line-height: 1.6;
            margin: 0;
            overflow-x: hidden;
        }

        .header {
            background: var(--terminal-bg);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo {
            color: var(--primary);
            font-size: 1.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .live-stats {
            display: flex;
            gap: 2rem;
            margin-right: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: bold;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .btn-hack {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            font-family: monospace;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-hack:hover {
            background: var(--primary);
            color: var(--dark);
        }

        .main-content {
            padding: 1rem 0;
            min-height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -0.5rem;
            height: 100%;
        }

        .col-12 {
            padding: 0 0.5rem;
            flex: 1;
        }

        .terminal-window {
            background: var(--terminal-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 3rem;
            border: 1px solid var(--primary);
            display: flex;
            flex-direction: column;
            height: 500px;
        }

        .terminal-header {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid var(--primary);
            flex-shrink: 0;
        }

        .terminal-button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: none;
        }

        .terminal-button.close { background: #ff5f56; }
        .terminal-button.minimize { background: #ffbd2e; }
        .terminal-button.maximize { background: #27c93f; }

        .terminal-content {
            padding: 1rem;
            flex: 1;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            line-height: 1.5;
            color: var(--text);
            position: relative;
            height: calc(100% - 40px);
            display: flex;
            flex-direction: column-reverse;
        }

        .command-line {
            margin-bottom: 0.5rem;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .command-line.active {
            opacity: 1;
            transform: translateY(0);
        }

        .prompt {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .command {
            color: var(--text);
        }

        .output {
            color: var(--text-muted);
            white-space: pre-wrap;
            margin-left: 1rem;
        }

        .attack-flow {
            background: var(--terminal-bg);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 1px solid var(--primary);
            position: relative;
            height: 500px;
            display: flex;
            flex-direction: column;
            margin-bottom: 3rem;
        }

        .flow-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 2rem;
            flex: 1;
            align-items: flex-start;
        }

        .flow-timeline::before {
            content: '';
            position: absolute;
            left: 2rem;
            right: 2rem;
            top: 2rem;
            height: 2px;
            background: var(--primary);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 1.5s ease;
            box-shadow: 0 0 10px var(--primary);
        }

        .flow-timeline.active::before {
            transform: scaleX(1);
        }

        .flow-step {
            flex: 1;
            text-align: center;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            padding: 0 2rem;
            margin: 0 2rem;
            max-width: 200px;
            margin: 0 auto;
        }

        .flow-step.active {
            opacity: 1;
            transform: translateY(0);
        }

        .step-icon {
            width: 3rem;
            height: 3rem;
            background: var(--terminal-bg);
            border: 2px solid var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.2);
        }

        .step-icon i {
            font-size: 1.2rem;
            color: var(--primary);
        }

        .step-content {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--primary);
            border-radius: 4px;
            padding: 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .step-content h4 {
            color: var(--primary);
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
        }

        .step-content p {
            color: var(--text-muted);
            margin: 0;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .progress-tracker {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.5s ease;
            box-shadow: 0 0 10px var(--primary);
        }

        .simulation-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 1rem;
        }

        @media (max-width: 992px) {
            .terminal-window {
                height: 300px;
            }

            .attack-flow {
                height: 250px;
            }

            .step-content {
                padding: 0.5rem;
            }

            .step-icon {
                width: 2.5rem;
                height: 2.5rem;
            }

            .step-icon i {
                font-size: 1rem;
            }
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .info-card {
            background: rgba(0, 255, 0, 0.02);
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 255, 0, 0.1);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-card-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .info-card-header h3 {
            color: var(--text);
            margin: 0;
            font-size: 1.2rem;
        }

        /* Stats Card */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.2);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-item.critical { color: #ff4444; }
        .stat-item.high { color: #ffbb33; }
        .stat-item.medium { color: #00C851; }
        .stat-item.low { color: #33b5e5; }

        /* Vector Card */
        .vector-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .vector-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .vector-icon {
            width: 40px;
            height: 40px;
            background: rgba(0, 255, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .vector-info {
            flex: 1;
        }

        .vector-name {
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .vector-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .vector-progress {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
            transition: width 1s ease;
        }

        .vector-value {
            margin-top: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Tips Card */
        .tips-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .tip-item {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .tip-item i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 0.2rem;
        }

        .tip-content h4 {
            color: var(--text);
            margin: 0 0 0.5rem 0;
            font-size: 1rem;
        }

        .tip-content p {
            color: var(--text-muted);
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Alerts Card */
        .alerts-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .alert-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.2);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .alert-item.critical .alert-icon { background: rgba(255, 68, 68, 0.2); color: #ff4444; }
        .alert-item.high .alert-icon { background: rgba(255, 187, 51, 0.2); color: #ffbb33; }
        .alert-item.medium .alert-icon { background: rgba(0, 200, 81, 0.2); color: #00C851; }

        /* Footer */
        footer {
            background: linear-gradient(180deg, var(--dark) 0%, rgba(0, 0, 0, 0.98) 100%);
            border-top: 2px solid var(--primary);
            padding: 4rem 0 2rem;
            position: relative;
            margin-top: 5rem;
        }

        footer::before {
            content: '';
            position: absolute;
            top: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(0, 255, 0, 0.5) 20%, 
                var(--primary) 50%,
                rgba(0, 255, 0, 0.5) 80%, 
                transparent 100%);
            box-shadow: 0 0 20px var(--primary);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 4rem;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .footer-logo-icon {
            font-size: 2.5rem;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .footer-logo-text {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--text);
            letter-spacing: 1px;
        }

        .footer-logo-text span {
            color: var(--primary);
        }

        .footer-description {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .footer-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .footer-section h3 {
            color: var(--text);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            position: relative;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-section h3 i {
            color: var(--primary);
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: var(--primary);
            box-shadow: 0 0 10px var(--primary);
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .footer-link {
            color: var(--text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.3rem 0;
        }

        .footer-link i {
            font-size: 0.8rem;
            color: var(--primary);
            transition: transform 0.3s ease;
        }

        .footer-link:hover {
            color: var(--primary);
            transform: translateX(5px);
        }

        .footer-link:hover i {
            transform: translateX(3px);
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-icon:hover {
            background: var(--primary);
            color: var(--dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 255, 0, 0.2);
        }

        .footer-bottom {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 255, 0, 0.1);
            text-align: center;
        }

        .footer-bottom-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .footer-bottom-links {
            display: flex;
            gap: 2rem;
        }

        .footer-bottom-link {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-bottom-link:hover {
            color: var(--primary);
        }

        @media (max-width: 992px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .footer-bottom-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .footer-bottom-links {
                justify-content: center;
            }
        }

        /* Nouvelles sections */
        .intro-section {
            padding: 4rem 0;
            background: linear-gradient(135deg, var(--dark) 0%, rgba(0, 40, 0, 0.98) 100%);
            position: relative;
            overflow: hidden;
        }

        .intro-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg ... />') repeat;
            opacity: 0.1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .lead {
            font-size: 1.2rem;
            color: var(--text-muted);
            margin-bottom: 3rem;
            max-width: 800px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 255, 0, 0.2);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: var(--text);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--text-muted);
            line-height: 1.6;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            color: var(--text);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        .simulation-section {
            padding: 4rem 0;
        }

        .simulation-container {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-panel {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--primary);
            border-radius: 8px;
            padding: 2rem;
        }

        .phase-info h3 {
            color: var(--text);
            margin-bottom: 1.5rem;
        }

        .phase-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .phase-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid rgba(0, 255, 0, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .phase-item[data-active="true"] {
            background: rgba(0, 255, 0, 0.1);
            border-color: var(--primary);
        }

        .phase-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .phase-content h4 {
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .phase-content p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .features-section {
            padding: 4rem 0;
            background: rgba(0, 255, 0, 0.02);
        }

        .features-grid.advanced .feature-card {
            text-align: left;
        }

        .feature-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .feature-link:hover {
            gap: 0.8rem;
        }

        .cta-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--dark) 0%, rgba(0, 40, 0, 0.98) 100%);
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-content h2 {
            color: var(--text);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-content p {
            color: var(--text-muted);
            margin-bottom: 2rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--dark);
            border: none;
        }

        .btn-primary:hover {
            background: #00ff00;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-secondary:hover {
            background: rgba(0, 255, 0, 0.1);
        }

        @media (max-width: 992px) {
            .simulation-container {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        /* Navigation */
        .main-header {
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 255, 0, 0.1);
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }

        .navbar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo i {
            color: var(--primary);
            font-size: 1.8rem;
        }

        .logo:hover {
            color: var(--primary);
        }

        .navbar-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-link {
            color: var(--text);
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 1rem;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .navbar-end {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(0, 255, 0, 0.1);
            transform: translateY(-2px);
        }

        /* Ajustements pour le responsive */
        @media (max-width: 992px) {
            .navbar-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(0, 0, 0, 0.95);
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                border-bottom: 1px solid rgba(0, 255, 0, 0.1);
            }

            .navbar-menu.active {
                display: flex;
            }

            .navbar-end {
                display: none;
            }

            .navbar-end.active {
                display: flex;
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        /* Ajustements pour la simulation */
        .simulation-section {
            margin-top: 80px; /* Pour compenser la navbar fixed */
        }

        .simulation-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 992px) {
            .simulation-container {
                grid-template-columns: 1.5fr 1fr;
            }
        }

        .terminal-window {
            height: 500px;
            margin-bottom: 0;
        }

        .info-panel {
            height: 500px;
            overflow-y: auto;
        }

        /* Ajustements généraux */
        main {
            padding-top: 60px; /* Pour compenser la navbar fixed */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        section {
            padding: 4rem 0;
        }

        /* Styles pour la section vidéo */
        .video-section {
            padding: 4rem 0;
            background: rgba(0, 0, 0, 0.8);
            position: relative;
            overflow: hidden;
        }

        .video-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .video-frame {
            width: 100%;
            aspect-ratio: 16/9;
            background: var(--terminal-bg);
            border: 1px solid var(--primary);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.2);
        }

        .video-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #000, #001100);
            color: var(--primary);
        }

        .video-title {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 2rem;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .terminal-animation {
            font-family: monospace;
            font-size: 1rem;
            line-height: 1.5;
            text-align: left;
            padding: 2rem;
            width: 80%;
            height: 60%;
            overflow: hidden;
            position: relative;
        }

        .cursor {
            display: inline-block;
            width: 10px;
            height: 20px;
            background: var(--primary);
            animation: blink 1s infinite;
            vertical-align: middle;
            margin-left: 5px;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        .typing-text {
            white-space: pre-wrap;
            margin: 0;
            animation: typing 4s steps(60, end) infinite;
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="container">
                <div class="navbar-brand">
                    <a href="index.php" class="logo">
                        <i class="bi bi-terminal-fill"></i>
                        NetboxCTF
                    </a>
                </div>
                <div class="navbar-menu">
                    <a href="training.php" class="nav-link">Formations</a>
                    <a href="labs.php" class="nav-link">Laboratoires</a>
                    <a href="challenges.php" class="nav-link">Challenges</a>
                    <a href="docs.php" class="nav-link">Documentation</a>
                    <a href="support.php" class="nav-link">Support</a>
                </div>
                <div class="navbar-end">
                    <a href="login.php" class="btn btn-outline">Connexion</a>
                    <a href="register.php" class="btn btn-primary">S'inscrire</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="intro-section">
            <div class="container">
                <h1>Plongez dans la Cybersécurité</h1>
                <p class="lead">Explorez les techniques avancées de sécurité informatique à travers des simulations réalistes et interactives</p>
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-shield-virus"></i>
                        <h3>Simulations Réalistes</h3>
                        <p>Expérimentez des scénarios d'attaque authentiques dans un environnement sécurisé</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Apprentissage Progressif</h3>
                        <p>Développez vos compétences étape par étape, des bases aux techniques avancées</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-users-cog"></i>
                        <h3>Communauté Active</h3>
                        <p>Rejoignez une communauté passionnée de professionnels et d'apprenants</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="video-section">
            <div class="video-container">
                <h2 class="video-title">Découvrez NetboxCTF</h2>
                <div class="video-frame">
                    <div class="video-placeholder">
                        <div class="terminal-animation">
                            <div class="typing-text">
> Bienvenue sur NetboxCTF
> Initialisation de l'environnement...
> Chargement des machines virtuelles...
> Configuration du réseau...
> Préparation des challenges...
> Système prêt !

[*] Plateforme d'entraînement cybersécurité
[*] Challenges pratiques et réalistes
[*] Environnement isolé et sécurisé
[*] Progression personnalisée</div>
                            <span class="cursor"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="simulation-section">
            <div class="container">
                <div class="section-header">
                    <h2>Simulation en Direct</h2>
                    <p>Observez une attaque simulée en temps réel et comprenez les différentes phases d'une cyberattaque</p>
                </div>
                <div class="simulation-container">
                    <div class="terminal-window">
                        <div class="terminal-header">
                            <div class="terminal-button close"></div>
                            <div class="terminal-button minimize"></div>
                            <div class="terminal-button maximize"></div>
                            <div class="terminal-title">Terminal</div>
                        </div>
                        <div class="terminal-content" id="terminal-content"></div>
                    </div>
                    <div class="info-panel">
                        <div class="phase-info">
                            <h3>Phases d'Attaque</h3>
                            <div class="phase-list">
                                <div class="phase-item" data-phase="0">
                                    <span class="phase-number">1</span>
                                    <div class="phase-content">
                                        <h4>Reconnaissance</h4>
                                        <p>Collecte d'informations sur la cible</p>
                                    </div>
                                </div>
                                <div class="phase-item" data-phase="1">
                                    <span class="phase-number">2</span>
                                    <div class="phase-content">
                                        <h4>Scan & Énumération</h4>
                                        <p>Identification des vulnérabilités</p>
                                    </div>
                                </div>
                                <div class="phase-item" data-phase="2">
                                    <span class="phase-number">3</span>
                                    <div class="phase-content">
                                        <h4>Exploitation</h4>
                                        <p>Exploitation des failles découvertes</p>
                                    </div>
                                </div>
                                <div class="phase-item" data-phase="3">
                                    <span class="phase-number">4</span>
                                    <div class="phase-content">
                                        <h4>Maintien d'Accès</h4>
                                        <p>Établissement d'une présence persistante</p>
                                    </div>
                                </div>
                                <div class="phase-item" data-phase="4">
                                    <span class="phase-number">5</span>
                                    <div class="phase-content">
                                        <h4>Exfiltration</h4>
                                        <p>Extraction des données sensibles</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features-section">
            <div class="container">
                <div class="section-header">
                    <h2>Fonctionnalités Avancées</h2>
                    <p>Découvrez nos outils spécialisés pour une formation complète en cybersécurité</p>
                </div>
                <div class="features-grid advanced">
                    <div class="feature-card">
                        <i class="fas fa-server"></i>
                        <h3>Laboratoires Virtuels</h3>
                        <p>Environnements isolés et sécurisés pour pratiquer sans risque</p>
                        <a href="labs.php" class="feature-link">En savoir plus <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-code"></i>
                        <h3>Challenges CTF</h3>
                        <p>Mettez vos compétences à l'épreuve avec nos défis réguliers</p>
                        <a href="challenges.php" class="feature-link">Voir les challenges <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-chart-line"></i>
                        <h3>Suivi de Progression</h3>
                        <p>Analysez votre évolution et identifiez vos points forts</p>
                        <a href="profile.php" class="feature-link">Voir mes stats <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-certificate"></i>
                        <h3>Certifications</h3>
                        <p>Obtenez des certifications reconnues dans l'industrie</p>
                        <a href="certifications.php" class="feature-link">Voir les certifications <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Prêt à Commencer ?</h2>
                    <p>Rejoignez notre communauté de plus de 10,000 apprenants et professionnels en cybersécurité</p>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn btn-primary">Commencer Gratuitement</a>
                        <a href="training.php" class="btn btn-secondary">Voir les Formations</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">
                    <i class="fas fa-shield-alt footer-logo-icon"></i>
                    <div class="footer-logo-text">Netbox<span>CTF</span></div>
                </div>
                <p class="footer-description">
                    Plateforme d'apprentissage avancée pour la cybersécurité. 
                    Simulez des attaques, apprenez les techniques de défense et 
                    maîtrisez la sécurité informatique dans un environnement contrôlé.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-icon" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="social-icon" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-icon" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="social-icon" title="Discord">
                        <i class="fab fa-discord"></i>
                    </a>
                </div>
            </div>

            <div class="footer-section">
                <h3><i class="fas fa-compass"></i> Navigation</h3>
                <div class="footer-links">
                    <a href="index.php" class="footer-link">
                        <i class="fas fa-chevron-right"></i>
                        Accueil
                    </a>
                    <a href="training.php" class="footer-link">
                        <i class="fas fa-chevron-right"></i>
                        Formations
                    </a>
                    <a href="labs.php" class="footer-link">
                        <i class="fas fa-chevron-right"></i>
                        Laboratoires
                    </a>
                    <a href="challenges.php" class="footer-link">
                        <i class="fas fa-chevron-right"></i>
                        Challenges
                    </a>
                    <a href="docs.php" class="footer-link">
                        <i class="fas fa-chevron-right"></i>
                        Documentation
                    </a>
                </div>
            </div>

            <div class="footer-section">
                <h3><i class="fas fa-headset"></i> Support</h3>
                <div class="footer-links">
                    <a href="contact.php" class="footer-link">
                        <i class="fas fa-envelope"></i>
                        contact@netboxctf.com
                    </a>
                    <a href="#" class="footer-link">
                        <i class="fas fa-phone"></i>
                        +33 (0)1 23 45 67 89
                    </a>
                    <a href="#" class="footer-link">
                        <i class="fas fa-map-marker-alt"></i>
                        Paris, France
                    </a>
                    <a href="#" class="footer-link">
                        <i class="fas fa-question-circle"></i>
                        FAQ
                    </a>
                    <a href="#" class="footer-link">
                        <i class="fas fa-life-ring"></i>
                        Support Technique
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="footer-bottom-text">
                    &copy; 2025 NetboxCTF. Tous droits réservés.
                </div>
                <div class="footer-bottom-links">
                    <a href="legal.php" class="footer-bottom-link">Mentions légales</a>
                    <a href="privacy.php" class="footer-bottom-link">Confidentialité</a>
                    <a href="terms.php" class="footer-bottom-link">CGU</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Simulation de commandes
            const randomSimulation = {
                commands: [
                    {
                        command: 'nmap -sV -p- 192.168.1.100',
                        output: 'Scanning...\nPort 80/tcp open  http\nPort 22/tcp open  ssh\nPort 3306/tcp open  mysql',
                        delay: 2000
                    },
                    {
                        command: 'dirb http://192.168.1.100/',
                        output: 'GENERATED WORDS: 4612\n\n---- Scanning URL: http://192.168.1.100/ ----\n==> DIRECTORY: /admin/\n==> DIRECTORY: /api/\n+ /login.php (CODE:200|SIZE:1234)',
                        delay: 3000
                    },
                    {
                        command: 'sqlmap -u "http://192.168.1.100/login.php" --forms --batch',
                        output: 'sqlmap identified the following injection point(s)...\n[INFO] GET parameter \'id\' is vulnerable. Do you want to keep testing? [y/N]',
                        delay: 2500
                    }
                ]
            };

            let currentCommand = 0;
            const terminal = document.querySelector('.terminal-content');

            // Fonction pour ajouter une commande au terminal
            function addCommand() {
                if (currentCommand >= randomSimulation.commands.length) {
                    currentCommand = 0;
                }

                const cmd = randomSimulation.commands[currentCommand];
                const cmdDiv = document.createElement('div');
                cmdDiv.className = 'command-line';

                const promptSpan = document.createElement('span');
                promptSpan.className = 'prompt';
                promptSpan.textContent = 'root@kali:~# ';

                const commandSpan = document.createElement('span');
                commandSpan.className = 'command';
                commandSpan.textContent = cmd.command;

                const outputDiv = document.createElement('div');
                outputDiv.className = 'output';
                outputDiv.textContent = cmd.output;

                cmdDiv.appendChild(promptSpan);
                cmdDiv.appendChild(commandSpan);
                cmdDiv.appendChild(outputDiv);

                terminal.insertBefore(cmdDiv, terminal.firstChild);
                
                // Animation d'apparition
                setTimeout(() => {
                    cmdDiv.classList.add('active');
                }, 100);

                // Limiter le nombre de commandes affichées
                const commandLines = terminal.getElementsByClassName('command-line');
                if (commandLines.length > 5) {
                    terminal.removeChild(commandLines[commandLines.length - 1]);
                }

                currentCommand++;
            }

            // Démarrer les simulation
            setInterval(addCommand, 4000);  // Nouvelle commande toutes les 4 secondes

            // Première exécution immédiate
            addCommand();
        });
    </script>
</body>
</html>
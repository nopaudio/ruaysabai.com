<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI Prompt Generator - Mobile Responsive</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e1e7ef 25%, #d1dae8 50%, #8fa8c7 75%, #5c7cfa 100%);
            min-height: 100vh;
            padding: 8px;
            position: relative;
            overflow-x: hidden;
            color: #1e293b;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border-radius: 20px;
            box-shadow: 
                0 30px 80px rgba(71, 85, 105, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.9),
                0 0 0 1px rgba(71, 85, 105, 0.1);
            overflow: hidden;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(71, 85, 105, 0.08);
        }
        
        .header {
            background: linear-gradient(135deg, #1E90FF 0%, #4169E1 25%, #6A5ACD 50%, #8A2BE2 100%);
            padding: 20px 15px;
            text-align: center;
            color: white;
            position: relative;
            border-bottom: 1px solid rgba(71, 85, 105, 0.1);
        }
        
        .header h1 {
            font-size: clamp(1.8rem, 6vw, 3.8rem);
            margin-bottom: 15px;
            font-weight: 800;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #ffffff, #f0f8ff, #e6f3ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
            word-break: break-word;
        }
        
        .header p {
            font-size: clamp(0.9rem, 3vw, 1.4rem);
            opacity: 0.95;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
        }
        
        .online-status {
            position: static;
            margin: 15px auto 0;
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            padding: 8px 16px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .online-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }
        
        .online-dot {
            width: 8px;
            height: 8px;
            background: linear-gradient(45deg, #22c55e, #16a34a);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }
        
        .main-content {
            display: block;
            padding: 15px;
        }
        
        .form-section, .result-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px 15px;
            border-radius: 20px;
            border: 1px solid rgba(71, 85, 105, 0.1);
            box-shadow: 0 10px 30px rgba(71, 85, 105, 0.08);
            position: relative;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .form-section::before, .result-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1E90FF, #4169E1, #6A5ACD, #8A2BE2);
            border-radius: 20px 20px 0 0;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            flex-wrap: wrap;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #1E90FF, #4169E1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            box-shadow: 0 8px 16px rgba(30, 144, 255, 0.3);
            flex-shrink: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #1e293b;
            backdrop-filter: blur(10px);
            -webkit-appearance: none;
            appearance: none;
        }
        
        .form-group select {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #64748b;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1E90FF;
            box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.15);
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 1);
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .generate-btn {
            background: linear-gradient(135deg, #1E90FF 0%, #4169E1 50%, #6A5ACD 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px rgba(30, 144, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(30, 144, 255, 0.4);
            background: linear-gradient(135deg, #1C86EE 0%, #3F5FBD 50%, #663399 100%);
        }
        
        .prompt-output {
            background: rgba(248, 250, 252, 0.9);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #1E90FF;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(71, 85, 105, 0.1);
        }
        
        .prompt-output h3 {
            color: #1e293b;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .prompt-text {
            background: rgba(241, 245, 249, 0.9);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid rgba(71, 85, 105, 0.1);
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
            max-height: 150px;
            overflow-y: auto;
            color: #334155;
            font-weight: 500;
            word-wrap: break-word;
            word-break: break-word;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-top: 12px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 6px 15px rgba(14, 165, 233, 0.25);
            width: 100%;
            justify-content: center;
        }
        
        .copy-btn:hover {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.35);
        }
        
        .placeholder-message {
            text-align: center;
            padding: 40px 15px;
            color: #64748b;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 15px;
            border: 2px dashed rgba(71, 85, 105, 0.2);
        }
        
        .placeholder-message i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
            color: #94a3b8;
        }
        
        .placeholder-message h3 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #334155;
        }
        
        .placeholder-message p {
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        /* Gallery Section */
        .gallery-section {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px 15px;
            border-radius: 20px;
            border: 1px solid rgba(71, 85, 105, 0.1);
            box-shadow: 0 10px 30px rgba(71, 85, 105, 0.08);
            position: relative;
        }
        
        .gallery-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1E90FF, #4169E1, #6A5ACD, #8A2BE2);
            border-radius: 20px 20px 0 0;
        }
        
        .gallery-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .gallery-header h2 {
            margin-bottom: 8px;
            font-size: 1.4rem;
            color: #1e293b;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .gallery-header p {
            margin-bottom: 15px;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .gallery-controls {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            justify-content: center;
        }
        
        .gallery-btn {
            background: linear-gradient(135deg, #1E90FF, #4169E1);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 6px 15px rgba(30, 144, 255, 0.25);
            flex: 1;
            justify-content: center;
            max-width: 120px;
        }
        
        .gallery-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(30, 144, 255, 0.35);
            background: linear-gradient(135deg, #1C86EE, #3F5FBD);
        }
        
        .horizontal-gallery {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.6));
            padding: 15px 0;
        }
        
        .gallery-grid {
            display: flex;
            gap: 15px;
            padding: 0 10px;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: rgba(30, 144, 255, 0.3) transparent;
            -webkit-overflow-scrolling: touch;
        }
        
        .gallery-grid::-webkit-scrollbar {
            height: 6px;
        }
        
        .gallery-grid::-webkit-scrollbar-track {
            background: rgba(248, 250, 252, 0.5);
            border-radius: 10px;
        }
        
        .gallery-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #1E90FF, #4169E1);
            border-radius: 10px;
        }
        
        .gallery-item {
            min-width: 280px;
            max-width: 280px;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(71, 85, 105, 0.12);
            transition: all 0.4s ease;
            border: 1px solid rgba(71, 85, 105, 0.08);
            position: relative;
        }
        
        .gallery-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #1E90FF, #6A5ACD, #8A2BE2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(71, 85, 105, 0.2);
        }
        
        .gallery-item:hover::before {
            opacity: 1;
        }
        
        .gallery-image {
            position: relative;
            height: 180px;
            overflow: hidden;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        }
        
        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .gallery-image:hover img {
            transform: scale(1.05);
        }
        
        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(30, 144, 255, 0.8), rgba(138, 43, 226, 0.7));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s ease;
            backdrop-filter: blur(3px);
        }
        
        .gallery-image:hover .gallery-overlay {
            opacity: 1;
        }
        
        .generate-image-btn {
            background: rgba(255, 255, 255, 0.95);
            color: #1E90FF;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .generate-image-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 1);
        }
        
        .gallery-content {
            padding: 20px;
        }
        
        .gallery-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .gallery-description {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 12px;
            font-style: italic;
            line-height: 1.3;
        }
        
        .gallery-prompt {
            background: linear-gradient(135deg, rgba(241, 245, 249, 0.9), rgba(248, 250, 252, 0.8));
            padding: 12px;
            border-radius: 10px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #334155;
            font-weight: 500;
            word-wrap: break-word;
            word-break: break-word;
            margin-bottom: 15px;
            border: 1px solid rgba(71, 85, 105, 0.1);
            max-height: 80px;
            overflow-y: auto;
            box-shadow: inset 0 1px 3px rgba(71, 85, 105, 0.05);
        }
        
        .gallery-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .gallery-actions .copy-btn {
            margin-top: 0;
            padding: 8px 14px;
            font-size: 12px;
            width: 100%;
        }
        
        .use-prompt-btn {
            background: linear-gradient(135deg, #8A2BE2, #6A5ACD);
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 6px 15px rgba(138, 43, 226, 0.25);
            width: 100%;
            justify-content: center;
        }
        
        .use-prompt-btn:hover {
            background: linear-gradient(135deg, #7B2CBF, #5A4FCF);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(138, 43, 226, 0.35);
        }
        
        /* Examples Section */
        .examples-section {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px 15px;
            border-radius: 20px;
            border: 1px solid rgba(71, 85, 105, 0.1);
            box-shadow: 0 10px 30px rgba(71, 85, 105, 0.08);
            position: relative;
        }
        
        .examples-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1E90FF, #4169E1, #6A5ACD, #8A2BE2);
            border-radius: 20px 20px 0 0;
        }
        
        .examples-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-direction: column;
            gap: 15px;
        }
        
        .examples-header h2 {
            color: #1e293b;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            flex-wrap: wrap;
        }
        
        .examples-header p {
            color: #64748b;
            margin-top: 5px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 15px rgba(14, 165, 233, 0.25);
            align-self: flex-start;
        }
        
        .refresh-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.35);
            background: linear-gradient(135deg, #0284c7, #0369a1);
        }
        
        .examples-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .example-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(71, 85, 105, 0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(71, 85, 105, 0.08);
        }
        
        .example-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1E90FF, #4169E1, #6A5ACD);
        }
        
        .example-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(71, 85, 105, 0.15);
            border-color: rgba(30, 144, 255, 0.3);
        }
        
        .example-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            font-size: 1rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .example-prompt {
            background: rgba(241, 245, 249, 0.9);
            padding: 12px;
            border-radius: 10px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 11px;
            line-height: 1.4;
            margin-bottom: 12px;
            max-height: 100px;
            overflow-y: auto;
            border: 1px solid rgba(71, 85, 105, 0.1);
            color: #334155;
            word-wrap: break-word;
            word-break: break-word;
        }
        
        /* Touch optimizations */
        @media (hover: none) and (pointer: coarse) {
            .gallery-item:hover,
            .example-card:hover,
            .generate-btn:hover,
            .copy-btn:hover,
            .gallery-btn:hover,
            .use-prompt-btn:hover,
            .refresh-btn:hover {
                transform: none;
            }
            
            .gallery-overlay {
                opacity: 0.9;
            }
            
            .gallery-image:hover img {
                transform: none;
            }
        }
        
        /* Very small screens */
        @media (max-width: 360px) {
            body {
                padding: 4px;
            }
            
            .header {
                padding: 15px 10px;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .form-section, .result-section, .gallery-section, .examples-section {
                padding: 15px 10px;
                margin-bottom: 15px;
            }
            
            .gallery-item {
                min-width: 260px;
                max-width: 260px;
            }
            
            .gallery-controls {
                flex-direction: column;
                gap: 6px;
            }
            
            .gallery-btn {
                max-width: none;
                width: 100%;
            }
        }
        
        /* Landscape orientation on mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .header {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }
            
            .header p {
                font-size: 0.8rem;
            }
            
            .online-status {
                margin: 8px auto 0;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="online-status">
                <div class="online-indicator">
                    <div class="online-dot"></div>
                    <span>24</span> คนออนไลน์
                </div>
            </div>
            <div class="header-content">
                <h1><i class="fas fa-brain"></i> AI Prompt Generator</h1>
                <p>สร้าง Prompt สำหรับ AI Image Generator ที่ให้ผลลัพธ์ภาพคมชัดและสวยงาม</p>
            </div>
        </div>
        
        <div class="main-content">
            <div class="form-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    สร้าง Prompt ของคุณ
                </div>
                
                <form id="promptForm">
                    <div class="form-group">
                        <label for="subject"><i class="fas fa-crosshairs"></i> หัวข้อหลัก:</label>
                        <input type="text" id="subject" name="subject" placeholder="เช่น beautiful woman, luxury car, modern house, delicious food">
                    </div>
                    
                    <div class="form-group">
                        <label for="content_type"><i class="fas fa-layer-group"></i> ประเภทเนื้อหา:</label>
                        <select id="content_type" name="content_type">
                            <option value="">เลือกประเภท</option>
                            <option value="portrait photography">บุคคล/ตัวละคร</option>
                            <option value="product photography">สินค้า/ผลิตภัณฑ์</option>
                            <option value="landscape photography">ธรรมชาติ/ทิวทัศน์</option>
                            <option value="interior design">ห้อง/สถาปัตยกรรม</option>
                            <option value="food photography">อาหาร/เครื่องดื่ม</option>
                            <option value="abstract art">ศิลปะ/นามธรรม</option>
                            <option value="automotive photography">ยานพาหนะ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="style"><i class="fas fa-palette"></i> สไตล์ภาพ:</label>
                        <select id="style" name="style">
                            <option value="">เลือกสไตล์</option>
                            <option value="photorealistic">รูปถ่ายจริง</option>
                            <option value="cinematic">ภาพยนตร์</option>
                            <option value="anime style">อนิเมะ</option>
                            <option value="oil painting">ภาพวาดสีน้ำมัน</option>
                            <option value="digital art">ดิจิทัลอาร์ต</option>
                            <option value="vintage">วินเทจ</option>
                            <option value="minimalist">มินิมัล</option>
                            <option value="cyberpunk">ไซเบอร์พังค์</option>
                            <option value="fantasy art">แฟนตาซี</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="scene"><i class="fas fa-mountain"></i> ฉากหลัง/สถานที่:</label>
                        <input type="text" id="scene" name="scene" placeholder="เช่น beautiful garden, modern office, cozy bedroom, city street">
                    </div>
                    
                    <div class="form-group">
                        <label for="details"><i class="fas fa-plus-circle"></i> รายละเอียดเพิ่มเติม:</label>
                        <textarea id="details" name="details" placeholder="เช่น เนื้อผิว, วัสดุ, ลวดลาย, การตกแต่ง หรือรายละเอียดพิเศษอื่นๆ"></textarea>
                    </div>
                    
                    <button type="submit" class="generate-btn">
                        <i class="fas fa-brain"></i> สร้าง Prompt
                    </button>
                </form>
            </div>
            
            <div class="result-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    ผลลัพธ์ Prompt
                </div>
                
                <div class="placeholder-message" id="placeholder-content">
                    <i class="fas fa-lightbulb"></i>
                    <h3>เริ่มสร้าง Prompt แรกของคุณ</h3>
                    <p>กรอกข้อมูลในฟอร์มด้านบน แล้วกดปุ่ม "สร้าง Prompt" เพื่อดูผลลัพธ์</p>
                </div>
            </div>
            
            <div class="examples-section">
                <div class="examples-header">
                    <div>
                        <h2>
                            <i class="fas fa-star"></i> ตัวอย่าง Prompt ยอดนิยม
                        </h2>
                        <p>คลิกเพื่อคัดลอก Prompt ที่คุณสนใจ - สุ่มใหม่ทุกครั้ง!</p>
                    </div>
                    <button class="refresh-btn" onClick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> สุ่มใหม่
                    </button>
                </div>
                
                <div class="examples-grid">
                    <div class="example-card">
                        <div class="example-title">
                            <i class="fas fa-female"></i> สาวสวยแฟชั่น
                        </div>
                        <div class="example-prompt" id="example-0">
                            beautiful woman, portrait photography style, photorealistic, elegant fashion dress, studio lighting, professional makeup, soft shadows, high resolution, ultra-detailed, cinematic quality
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard('example-0', this)">
                            <i class="fas fa-copy"></i> คัดลอก
                        </button>
                    </div>
                    
                    <div class="example-card">
                        <div class="example-title">
                            <i class="fas fa-car"></i> รถหรู
                        </div>
                        <div class="example-prompt" id="example-1">
                            luxury sports car, automotive photography style, sleek metallic paint finish, dramatic studio lighting, reflective black floor, modern showroom background, ultra-detailed, professional photography
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard('example-1', this)">
                            <i class="fas fa-copy"></i> คัดลอก
                        </button>
                    </div>
                    
                    <div class="example-card">
                        <div class="example-title">
                            <i class="fas fa-home"></i> บ้านโมเดิร์น
                        </div>
                        <div class="example-prompt" id="example-2">
                            modern house, interior design style, minimalist furniture, natural lighting, clean lines, neutral colors, high-end materials, spacious living room, floor-to-ceiling windows, photorealistic
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard('example-2', this)">
                            <i class="fas fa-copy"></i> คัดลอก
                        </button>
                    </div>
                    
                    <div class="example-card">
                        <div class="example-title">
                            <i class="fas fa-utensils"></i> อาหารอร่อย
                        </div>
                        <div class="example-prompt" id="example-3">
                            delicious food, food photography style, gourmet presentation, natural lighting, fresh ingredients, artistic plating, shallow depth of field, macro lens, appetizing colors, professional styling
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard('example-3', this)">
                            <i class="fas fa-copy"></i> คัดลอก
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="gallery-section">
                <div class="gallery-header">
                    <div>
                        <h2>
                            <i class="fas fa-images"></i> แกลเลอรี่ Prompt พร้อมตัวอย่าง
                        </h2>
                        <p>รวบรวม Prompt คุณภาพสูงพร้อมภาพตัวอย่าง คลิกเพื่อคัดลอกหรือใช้ในฟอร์ม</p>
                    </div>
                    <div class="gallery-controls">
                        <button class="gallery-btn" onClick="previousSlide()">
                            <i class="fas fa-chevron-left"></i> ก่อนหน้า
                        </button>
                        <button class="gallery-btn" onClick="nextSlide()">
                            ถัดไป <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="horizontal-gallery">
                    <div class="gallery-grid" id="gallery-container">
                        <div class="gallery-item">
                            <div class="gallery-image">
                                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=300&fit=crop&crop=face" 
                                     alt="Beautiful Woman Portrait"
                                     loading="lazy">
                                <div class="gallery-overlay">
                                    <button class="generate-image-btn" onclick="openImageGenerator('beautiful woman, portrait photography, natural lighting, soft makeup, elegant pose, professional photo, high resolution, photorealistic')">
                                        <i class="fas fa-magic"></i> สร้างภาพ
                                    </button>
                                </div>
                            </div>
                            <div class="gallery-content">
                                <h3 class="gallery-title">
                                    <i class="fas fa-female"></i>
                                    Portrait สาวสวย
                                </h3>
                                <p class="gallery-description">ภาพถ่าย Portrait ผู้หญิงสวยในสไตล์ธรรมชาติ</p>
                                <div class="gallery-prompt" id="gallery-0">
                                    beautiful woman, portrait photography, natural lighting, soft makeup, elegant pose, professional photo, high resolution, photorealistic, studio quality, sharp focus
                                </div>
                                <div class="gallery-actions">
                                    <button class="copy-btn" onclick="copyToClipboard('gallery-0', this)">
                                        <i class="fas fa-copy"></i> คัดลอก
                                    </button>
                                    <button class="use-prompt-btn" onclick="usePromptInForm('beautiful woman, portrait photography, natural lighting, soft makeup, elegant pose, professional photo, high resolution, photorealistic, studio quality, sharp focus')">
                                        <i class="fas fa-edit"></i> ใช้ในฟอร์ม
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="gallery-item">
                            <div class="gallery-image">
                                <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=400&h=300&fit=crop" 
                                     alt="Luxury Sports Car"
                                     loading="lazy">
                                <div class="gallery-overlay">
                                    <button class="generate-image-btn" onclick="openImageGenerator('luxury sports car, automotive photography, sleek design, metallic paint, dramatic lighting, showroom, ultra-detailed, photorealistic')">
                                        <i class="fas fa-magic"></i> สร้างภาพ
                                    </button>
                                </div>
                            </div>
                            <div class="gallery-content">
                                <h3 class="gallery-title">
                                    <i class="fas fa-car"></i>
                                    รถสปอร์ตหรู
                                </h3>
                                <p class="gallery-description">รถสปอร์ตสุดหรูในห้องแสดงรถ</p>
                                <div class="gallery-prompt" id="gallery-1">
                                    luxury sports car, automotive photography, sleek design, metallic paint, dramatic lighting, showroom, ultra-detailed, photorealistic, professional photography, cinematic
                                </div>
                                <div class="gallery-actions">
                                    <button class="copy-btn" onclick="copyToClipboard('gallery-1', this)">
                                        <i class="fas fa-copy"></i> คัดลอก
                                    </button>
                                    <button class="use-prompt-btn" onclick="usePromptInForm('luxury sports car, automotive photography, sleek design, metallic paint, dramatic lighting, showroom, ultra-detailed, photorealistic, professional photography, cinematic')">
                                        <i class="fas fa-edit"></i> ใช้ในฟอร์ม
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="gallery-item">
                            <div class="gallery-image">
                                <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=300&fit=crop" 
                                     alt="Modern Interior"
                                     loading="lazy">
                                <div class="gallery-overlay">
                                    <button class="generate-image-btn" onclick="openImageGenerator('modern interior design, minimalist living room, natural lighting, clean lines, neutral colors, contemporary furniture, spacious, photorealistic')">
                                        <i class="fas fa-magic"></i> สร้างภาพ
                                    </button>
                                </div>
                            </div>
                            <div class="gallery-content">
                                <h3 class="gallery-title">
                                    <i class="fas fa-home"></i>
                                    ห้องนั่งเล่นโมเดิร์น
                                </h3>
                                <p class="gallery-description">การตกแต่งภายในสไตล์โมเดิร์นมินิมัล</p>
                                <div class="gallery-prompt" id="gallery-2">
                                    modern interior design, minimalist living room, natural lighting, clean lines, neutral colors, contemporary furniture, spacious, photorealistic, architectural photography
                                </div>
                                <div class="gallery-actions">
                                    <button class="copy-btn" onclick="copyToClipboard('gallery-2', this)">
                                        <i class="fas fa-copy"></i> คัดลอก
                                    </button>
                                    <button class="use-prompt-btn" onclick="usePromptInForm('modern interior design, minimalist living room, natural lighting, clean lines, neutral colors, contemporary furniture, spacious, photorealistic, architectural photography')">
                                        <i class="fas fa-edit"></i> ใช้ในฟอร์ม
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Generate Prompt function
        function generatePrompt() {
            const subject = document.getElementById('subject').value.trim();
            const contentType = document.getElementById('content_type').value;
            const style = document.getElementById('style').value;
            const scene = document.getElementById('scene').value.trim();
            const details = document.getElementById('details').value.trim();
            
            if (!subject && !contentType && !style && !scene && !details) {
                alert('กรุณากรอกข้อมูลอย่างน้อย 1 ช่อง');
                return;
            }
            
            let prompt = '';
            const baseQuality = 'masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting';
            
            if (subject) prompt += subject + ', ';
            if (contentType) prompt += contentType + ', ';
            if (style) prompt += style + ' style, ';
            if (scene) prompt += 'in ' + scene + ', ';
            if (details) prompt += details + ', ';
            
            prompt += baseQuality;
            
            // Show result
            const resultSection = document.querySelector('.result-section');
            resultSection.innerHTML = `
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    ผลลัพธ์ Prompt
                </div>
                
                <div class="prompt-output">
                    <h3><i class="fas fa-check-circle"></i> Prompt ที่สร้างขึ้น:</h3>
                    <div class="prompt-text" id="generated-prompt">${prompt}</div>
                    <button class="copy-btn" onclick="copyToClipboard('generated-prompt', this)">
                        <i class="fas fa-copy"></i> คัดลอก Prompt
                    </button>
                </div>
            `;
        }

        // Copy to clipboard function
        function copyToClipboard(elementId, buttonElement) {
            const element = document.getElementById(elementId);
            if (!element) {
                alert('ไม่พบข้อมูลที่จะคัดลอก');
                return;
            }
            
            const text = element.innerText || element.textContent;
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess(buttonElement);
                }).catch(function() {
                    fallbackCopyToClipboard(text, buttonElement);
                });
            } else {
                fallbackCopyToClipboard(text, buttonElement);
            }
        }

        function fallbackCopyToClipboard(text, buttonElement) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-9999px";
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess(buttonElement);
            } catch (err) {
                alert('คัดลอก Prompt นี้:\n\n' + text);
            }
            
            document.body.removeChild(textArea);
        }

        function showCopySuccess(buttonElement) {
            if (!buttonElement) return;
            
            const originalHTML = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fas fa-check"></i> คัดลอกแล้ว!';
            buttonElement.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
            
            setTimeout(function() {
                buttonElement.innerHTML = originalHTML;
                buttonElement.style.background = 'linear-gradient(135deg, #0ea5e9, #0284c7)';
            }, 2000);
        }

        // Gallery navigation
        function previousSlide() {
            const gallery = document.getElementById('gallery-container');
            if (gallery) {
                gallery.scrollBy({
                    left: -295,
                    behavior: 'smooth'
                });
            }
        }
        
        function nextSlide() {
            const gallery = document.getElementById('gallery-container');
            if (gallery) {
                gallery.scrollBy({
                    left: 295,
                    behavior: 'smooth'
                });
            }
        }

        // Use prompt in form
        function usePromptInForm(prompt) {
            const words = prompt.toLowerCase();
            
            // Clear form first
            document.getElementById('subject').value = '';
            document.getElementById('content_type').value = '';
            document.getElementById('style').value = '';
            document.getElementById('scene').value = '';
            document.getElementById('details').value = '';
            
            // Auto-fill form based on prompt content
            if (words.includes('woman') || words.includes('man') || words.includes('person') || words.includes('model')) {
                document.getElementById('content_type').value = 'portrait photography';
            } else if (words.includes('car') || words.includes('vehicle')) {
                document.getElementById('content_type').value = 'automotive photography';
            } else if (words.includes('landscape') || words.includes('mountain')) {
                document.getElementById('content_type').value = 'landscape photography';
            } else if (words.includes('interior') || words.includes('room')) {
                document.getElementById('content_type').value = 'interior design';
            } else if (words.includes('food')) {
                document.getElementById('content_type').value = 'food photography';
            }
            
            // Fill style
            if (words.includes('photorealistic')) {
                document.getElementById('style').value = 'photorealistic';
            } else if (words.includes('cinematic')) {
                document.getElementById('style').value = 'cinematic';
            } else if (words.includes('anime')) {
                document.getElementById('style').value = 'anime style';
            }
            
            // Fill subject
            const subjectMatch = prompt.match(/^([^,]+)/);
            if (subjectMatch) {
                document.getElementById('subject').value = subjectMatch[1].trim();
            }
            
            // Scroll to form
            document.querySelector('.form-section').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
            
            alert('✨ ข้อมูลจาก Prompt ถูกกรอกลงในฟอร์มแล้ว! คุณสามารถปรับแต่งเพิ่มเติมได้');
        }

        // Open image generator
        function openImageGenerator(prompt = '') {
            const imageGenSites = [
                'https://stablediffusionweb.com/',
                'https://playgroundai.com/',
                'https://leonardo.ai/',
                'https://www.midjourney.com/'
            ];
            
            const randomSite = imageGenSites[Math.floor(Math.random() * imageGenSites.length)];
            
            if (prompt) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(prompt).then(function() {
                        setTimeout(() => {
                            window.open(randomSite, '_blank');
                            alert('🎨 Prompt ถูกคัดลอกแล้ว! วางลงใน AI Image Generator ที่เปิดขึ้น');
                        }, 500);
                    });
                } else {
                    window.open(randomSite, '_blank');
                    alert('🎨 กรุณาคัดลอก Prompt นี้: ' + prompt);
                }
            } else {
                window.open(randomSite, '_blank');
            }
        }

        // Event listeners
        document.getElementById('promptForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generatePrompt();
        });

        // Auto-scroll gallery every 15 seconds
        function autoScrollGallery() {
            const gallery = document.getElementById('gallery-container');
            if (gallery) {
                const scrollWidth = gallery.scrollWidth;
                const clientWidth = gallery.clientWidth;
                const currentScroll = gallery.scrollLeft;
                
                if (currentScroll + clientWidth >= scrollWidth - 50) {
                    gallery.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    gallery.scrollBy({ left: 295, behavior: 'smooth' });
                }
            }
        }
        
        setInterval(autoScrollGallery, 15000);

        // Smooth scrolling for mobile
        document.addEventListener('touchstart', function() {}, {passive: true});
        document.addEventListener('touchmove', function() {}, {passive: true});
    </script>
</body>
</html>
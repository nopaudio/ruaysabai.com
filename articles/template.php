<?php
// Template สำหรับบทความ - คัดลอกไฟล์นี้เพื่อสร้างบทความใหม่

// ข้อมูล SEO สำหรับบทความนี้
$article = [
    'title' => 'ชื่อบทความ - Prompt AI Thailand',
    'description' => 'คำอธิบายบทความสั้นๆ ประมาณ 120-160 ตัวอักษร สำหรับ Google Search',
    'keywords' => 'prompt, AI, คำค้นหาหลัก, คำค้นหารอง',
    'author' => 'Prompt AI Thailand',
    'publish_date' => '2025-06-04',
    'modified_date' => '2025-06-04',
    'category' => 'AI Guides',
    'image' => '/images/articles/article-image.jpg',
    'canonical' => 'https://yoursite.com/articles/article-slug.php'
];

require_once '../config.php';
$pageData = getPageData();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($article['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($article['description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($article['keywords']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($article['author']); ?>">
    <link rel="canonical" href="<?php echo $article['canonical']; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo $article['canonical']; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($article['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($article['description']); ?>">
    <meta property="og:image" content="<?php echo $article['image']; ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($pageData['settings']['site_title']); ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $article['canonical']; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($article['title']); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($article['description']); ?>">
    <meta property="twitter:image" content="<?php echo $article['image']; ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <meta name="theme-color" content="#6A5ACD">
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    
    <!-- Article-specific styles -->
    <style>
        .article-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            margin-top: 20px;
            margin-bottom: 40px;
        }
        
        .article-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .article-title {
            font-size: 2.2em;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .article-meta {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9em;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .article-description {
            font-size: 1.1em;
            color: #4a5568;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .article-content {
            font-size: 1.05em;
            line-height: 1.8;
            color: #2d3748;
        }
        
        .article-content h2 {
            font-size: 1.6em;
            color: #1a202c;
            margin: 40px 0 20px 0;
            font-weight: 700;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        
        .article-content h3 {
            font-size: 1.3em;
            color: #2d3748;
            margin: 30px 0 15px 0;
            font-weight: 600;
        }
        
        .article-content p {
            margin-bottom: 20px;
        }
        
        .article-content ul, .article-content ol {
            margin: 20px 0;
            padding-left: 30px;
        }
        
        .article-content li {
            margin-bottom: 10px;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #f0f8ff 0%, #e6e9fc 100%);
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .highlight-box h4 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .code-example {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
        }
        
        .breadcrumb {
            margin: 20px 0;
            font-size: 0.9em;
        }
        
        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .article-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            gap: 20px;
        }
        
        .nav-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .related-articles {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin-top: 40px;
        }
        
        .related-articles h3 {
            color: #1a202c;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .related-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .related-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .related-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .related-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95em;
        }
        
        .related-item a:hover {
            color: #764ba2;
        }
        
        @media (max-width: 768px) {
            .article-container {
                margin: 10px;
                padding: 15px;
            }
            
            .article-title {
                font-size: 1.8em;
            }
            
            .article-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .article-navigation {
                flex-direction: column;
            }
            
            .related-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <!-- Schema.org Article Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?php echo htmlspecialchars($article['title']); ?>",
        "description": "<?php echo htmlspecialchars($article['description']); ?>",
        "image": "<?php echo $article['image']; ?>",
        "author": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($article['author']); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($pageData['settings']['site_title']); ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "https://yoursite.com/logo.png"
            }
        },
        "datePublished": "<?php echo $article['publish_date']; ?>",
        "dateModified": "<?php echo $article['modified_date']; ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?php echo $article['canonical']; ?>"
        }
    }
    </script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="user-menu-container">
                <div class="user-menu">
                    <a href="../index.php"><i class="fas fa-home"></i> หน้าหลัก</a>
                    <a href="index.php"><i class="fas fa-book"></i> บทความทั้งหมด</a>
                </div>
            </div>
            
            <div class="header-content">
                <h1><i class="fas fa-brain"></i> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?></h1>
                <p>คู่มือและเทคนิคการใช้งาน AI</p>
            </div>
        </header>
        
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="../index.php">หน้าหลัก</a> > 
            <a href="index.php">บทความ</a> > 
            <span><?php echo htmlspecialchars($article['category']); ?></span>
        </nav>
        
        <!-- Article -->
        <article class="article-container">
            <header class="article-header">
                <h1 class="article-title"><?php echo htmlspecialchars(str_replace(' - Prompt AI Thailand', '', $article['title'])); ?></h1>
                
                <div class="article-meta">
                    <span class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d/m/Y', strtotime($article['publish_date'])); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($article['author']); ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-folder"></i>
                        <?php echo htmlspecialchars($article['category']); ?>
                    </span>
                </div>
                
                <p class="article-description"><?php echo htmlspecialchars($article['description']); ?></p>
            </header>
            
            <div class="article-content">
                <!-- เนื้อหาบทความจะอยู่ตรงนี้ -->
                <!-- ใส่เนื้อหาของแต่ละบทความที่นี่ -->
                
                <h2>หัวข้อหลักที่ 1</h2>
                <p>เนื้อหาของหัวข้อแรก...</p>
                
                <div class="highlight-box">
                    <h4><i class="fas fa-lightbulb"></i> เทคนิคสำคัญ</h4>
                    <p>ข้อมูลที่สำคัญหรือเทคนิคพิเศษ...</p>
                </div>
                
                <h3>หัวข้อย่อย</h3>
                <p>เนื้อหาของหัวข้อย่อย...</p>
                
                <div class="code-example">
                    ตัวอย่าง Prompt:<br>
                    "beautiful portrait photography, professional lighting, 8k resolution"
                </div>
                
                <h2>หัวข้อหลักที่ 2</h2>
                <p>เนื้อหาต่อไป...</p>
                
                <!-- เพิ่มเนื้อหาตามต้องการ -->
            </div>
            
            <!-- Navigation -->
            <nav class="article-navigation">
                <a href="#" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> บทความก่อนหน้า
                </a>
                <a href="../index.php" class="nav-btn">
                    <i class="fas fa-magic"></i> สร้าง Prompt
                </a>
                <a href="#" class="nav-btn">
                    บทความถัดไป <i class="fas fa-arrow-right"></i>
                </a>
            </nav>
            
            <!-- Related Articles -->
            <section class="related-articles">
                <h3><i class="fas fa-bookmark"></i> บทความที่เกี่ยวข้อง</h3>
                <div class="related-list">
                    <div class="related-item">
                        <a href="ai-art-styles.php">สำรวจสไตล์ภาพยอดนิยมใน AI Art</a>
                        <p style="font-size: 0.8em; color: #6b7280; margin-top: 5px;">เปรียบเทียบสไตล์ต่างๆ สำหรับการสร้างภาพ AI</p>
                    </div>
                    <div class="related-item">
                        <a href="ai-tools-comparison.php">เปรียบเทียบ AI Tools ยอดนิยม</a>
                        <p style="font-size: 0.8em; color: #6b7280; margin-top: 5px;">Midjourney vs DALL-E vs Stable Diffusion</p>
                    </div>
                    <div class="related-item">
                        <a href="negative-prompt-guide.php">คู่มือ Negative Prompt</a>
                        <p style="font-size: 0.8em; color: #6b7280; margin-top: 5px;">เทคนิคการใช้ Negative Prompt อย่างมืออาชีพ</p>
                    </div>
                </div>
            </section>
        </article>
        
        <!-- Footer -->
        <footer class="site-footer">
            <div class="footer-links">
                <a href="../index.php">หน้าหลัก</a> |
                <a href="index.php">บทความทั้งหมด</a> |
                <a href="../about.php">เกี่ยวกับเรา</a> |
                <a href="../contact.php">ติดต่อเรา</a>
            </div>
            <p class="footer-copyright">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?>. สงวนลิขสิทธิ์</p>
        </footer>
    </div>
    
    <!-- Analytics และ Scripts อื่นๆ -->
    <script>
        // Google Analytics หรือ tracking scripts
        // Social sharing scripts
        // Table of contents auto-generation
        
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-generate table of contents
            const headings = document.querySelectorAll('.article-content h2, .article-content h3');
            if (headings.length > 3) {
                // สร้าง TOC ถ้ามีหัวข้อมากกว่า 3 หัวข้อ
                generateTableOfContents(headings);
            }
        });
        
        function generateTableOfContents(headings) {
            // Function to generate TOC
            // Implementation here
        }
    </script>
</body>
</html>
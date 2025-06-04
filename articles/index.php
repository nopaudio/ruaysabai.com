<?php
require_once '../config.php';
$pageData = getPageData();

// ข้อมูลบทความทั้งหมด
$articles = [
    [
        'title' => '5 เทคนิคเขียน Prompt ให้ AI เข้าใจง่าย ได้ภาพสวยตรงใจ',
        'description' => 'เรียนรู้เทคนิคการเขียน Prompt ที่มืออาชีพ เพื่อให้ได้ภาพ AI ที่สวยงามและตรงตามความต้องการ',
        'url' => 'prompt-writing-guide.php',
        'category' => 'เทคนิค',
        'date' => '2025-06-04',
        'reading_time' => '8 นาที',
        'icon' => 'fas fa-lightbulb',
        'featured' => true
    ],
    [
        'title' => 'สำรวจสไตล์ภาพยอดนิยมใน AI Art Generator',
        'description' => 'เปรียบเทียบ Photorealistic, Anime, Cinematic และสไตล์อื่นๆ เพื่อเลือกใช้ให้เหมาะกับงาน',
        'url' => 'ai-art-styles.php',
        'category' => 'สไตล์',
        'date' => '2025-06-03',
        'reading_time' => '6 นาที',
        'icon' => 'fas fa-palette',
        'featured' => true
    ],
    [
        'title' => 'เปรียบเทียบ AI Tools: Midjourney vs DALL-E vs Stable Diffusion',
        'description' => 'รู้จักข้อดีข้อเสียของแต่ละเครื่องมือ AI สร้างภาพ เพื่อเลือกใช้ให้เหมาะกับงาน',
        'url' => 'ai-tools-comparison.php',
        'category' => 'เครื่องมือ',
        'date' => '2025-06-02',
        'reading_time' => '10 นาที',
        'icon' => 'fas fa-magic',
        'featured' => true
    ],
    [
        'title' => 'คู่มือ Negative Prompt ฉบับสมบูรณ์',
        'description' => 'เทคนิคการใช้ Negative Prompt เพื่อกำจัดสิ่งที่ไม่ต้องการในภาพ ให้ได้ผลลัพธ์ที่ดีขึ้น',
        'url' => 'negative-prompt-guide.php',
        'category' => 'เทคนิค',
        'date' => '2025-06-01',
        'reading_time' => '7 นาที',
        'icon' => 'fas fa-times-circle',
        'featured' => false
    ]
];

// แยกบทความตามหมวดหมู่
$categories = [];
foreach ($articles as $article) {
    $categories[$article['category']][] = $article;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บทความและคู่มือ AI - <?php echo htmlspecialchars($pageData['settings']['site_title']); ?></title>
    <meta name="description" content="รวมบทความ คู่มือ และเทคนิคการใช้งาน AI สร้างภาพ เขียน Prompt และเครื่องมือต่างๆ ที่น่าสนใจ">
    <meta name="keywords" content="บทความ AI, คู่มือ AI, เทคนิค Prompt, AI Art Guide, Midjourney, DALL-E">
    
    <!-- Open Graph -->
    <meta property="og:title" content="บทความและคู่มือ AI - <?php echo htmlspecialchars($pageData['settings']['site_title']); ?>">
    <meta property="og:description" content="รวมบทความ คู่มือ และเทคนิคการใช้งาน AI สร้างภาพ เขียน Prompt และเครื่องมือต่างๆ">
    <meta property="og:type" content="website">
    
    <link rel="canonical" href="https://yoursite.com/articles/">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    
    <style>
        .articles-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
        }
        
        .page-header h1 {
            font-size: 2.5em;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .page-header p {
            font-size: 1.2em;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
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
        
        .featured-articles {
            margin-bottom: 50px;
        }
        
        .section-title {
            font-size: 1.8em;
            color: #1a202c;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }
        
        .section-icon {
            color: #667eea;
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            position: relative;
        }
        
        .article-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
        }
        
        .article-card.featured {
            border-color: #10b981;
        }
        
        .article-card.featured::before {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .article-header {
            padding: 25px;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85em;
            color: #6b7280;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .category-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .featured .category-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .article-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .article-title a {
            text-decoration: none;
            color: inherit;
        }
        
        .article-title a:hover {
            color: #667eea;
        }
        
        .article-description {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .read-more-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .read-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .article-icon {
            font-size: 2em;
            color: #667eea;
            opacity: 0.7;
        }
        
        .categories-section {
            margin-top: 50px;
        }
        
        .category-group {
            margin-bottom: 40px;
            background: #f8fafc;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid #e2e8f0;
        }
        
        .category-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .category-title {
            font-size: 1.4em;
            color: #1a202c;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-articles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .compact-article-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .compact-article-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        
        .compact-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .compact-title a {
            text-decoration: none;
            color: #1a202c;
        }
        
        .compact-title a:hover {
            color: #667eea;
        }
        
        .compact-description {
            font-size: 0.9em;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        
        .compact-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8em;
            color: #9ca3af;
        }
        
        .cta-section {
            text-align: center;
            margin: 50px 0;
            padding: 40px;
            background: linear-gradient(135deg, #f0f8ff 0%, #e6e9fc 100%);
            border-radius: 20px;
            border: 2px solid #667eea;
        }
        
        .cta-title {
            font-size: 1.6em;
            color: #1a202c;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .cta-description {
            color: #4a5568;
            margin-bottom: 25px;
            font-size: 1.1em;
        }
        
        .cta-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(102, 126, 234, 0.3);
        }
        
        @media (max-width: 768px) {
            .articles-container {
                padding: 15px;
            }
            
            .page-header {
                padding: 30px 15px;
            }
            
            .page-header h1 {
                font-size: 2em;
            }
            
            .featured-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .category-articles {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .article-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="user-menu-container">
                <div class="user-menu">
                    <a href="../index.php"><i class="fas fa-home"></i> หน้าหลัก</a>
                    <a href="../index.php#new-prompt-generator-ui"><i class="fas fa-magic"></i> สร้าง Prompt</a>
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
            <span>บทความทั้งหมด</span>
        </nav>
        
        <div class="articles-container">
            <!-- Page Header -->
            <section class="page-header">
                <h1><i class="fas fa-book-open"></i> บทความและคู่มือ AI</h1>
                <p>รวมเทคนิค คู่มือ และความรู้เกี่ยวกับการใช้งาน AI สร้างภาพ การเขียน Prompt และเครื่องมือต่างๆ ที่จะช่วยให้คุณสร้างผลงานได้อย่างมืออาชีพ</p>
            </section>
            
            <!-- Featured Articles -->
            <section class="featured-articles">
                <h2 class="section-title">
                    <span class="section-icon"><i class="fas fa-star"></i></span>
                    บทความแนะนำ
                </h2>
                
                <div class="featured-grid">
                    <?php foreach ($articles as $article): ?>
                        <?php if ($article['featured']): ?>
                            <article class="article-card featured">
                                <div class="article-header">
                                    <div class="article-meta">
                                        <span class="category-badge"><?php echo htmlspecialchars($article['category']); ?></span>
                                        <span class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($article['date'])); ?>
                                        </span>
                                        <span class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($article['reading_time']); ?>
                                        </span>
                                    </div>
                                    
                                    <h3 class="article-title">
                                        <a href="<?php echo htmlspecialchars($article['url']); ?>">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <p class="article-description">
                                        <?php echo htmlspecialchars($article['description']); ?>
                                    </p>
                                    
                                    <div class="article-footer">
                                        <a href="<?php echo htmlspecialchars($article['url']); ?>" class="read-more-btn">
                                            อ่านบทความ <i class="fas fa-arrow-right"></i>
                                        </a>
                                        <i class="article-icon <?php echo htmlspecialchars($article['icon']); ?>"></i>
                                    </div>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- CTA Section -->
            <section class="cta-section">
                <h2 class="cta-title">พร้อมลองสร้าง Prompt แล้วหรือยัง?</h2>
                <p class="cta-description">ใช้ความรู้จากบทความเหล่านี้ มาสร้าง Prompt มืออาชีพด้วยเครื่องมือของเรา</p>
                <a href="../index.php#new-prompt-generator-ui" class="cta-button">
                    <i class="fas fa-magic"></i> สร้าง Prompt ทันที
                </a>
            </section>
            
            <!-- Categories Section -->
            <section class="categories-section">
                <h2 class="section-title">
                    <span class="section-icon"><i class="fas fa-folder"></i></span>
                    บทความตามหมวดหมู่
                </h2>
                
                <?php foreach ($categories as $categoryName => $categoryArticles): ?>
                    <div class="category-group">
                        <div class="category-header">
                            <h3 class="category-title">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($categoryName); ?>
                            </h3>
                        </div>
                        
                        <div class="category-articles">
                            <?php foreach ($categoryArticles as $article): ?>
                                <article class="compact-article-card">
                                    <h4 class="compact-title">
                                        <a href="<?php echo htmlspecialchars($article['url']); ?>">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h4>
                                    
                                    <p class="compact-description">
                                        <?php echo htmlspecialchars($article['description']); ?>
                                    </p>
                                    
                                    <div class="compact-meta">
                                        <span>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('d/m/Y', strtotime($article['date'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($article['reading_time']); ?>
                                        </span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
        
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
</body>
</html>
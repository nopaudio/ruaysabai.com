<?php include 'data.php'; ?>
<?php
session_start();
error_reporting(0);
require_once("system/a_func.php");
if (isset($_SESSION['id'])) {
    $q1 = dd_q("SELECT * FROM users WHERE id = ? LIMIT 1", [$_SESSION['id']]);
    if ($q1->rowCount() == 1) {
        $user = $q1->fetch(PDO::FETCH_ASSOC);
    } else {
        session_destroy();
        $_GET['page'] = "login";
    }
}
$get_static = dd_q("SELECT * FROM static");
$static = $get_static->fetch(PDO::FETCH_ASSOC);
// $config["pri_color"]   = "#FF2B2B";
// $config["sec_color"]  = "#9A0D0D";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="<?php echo $config['name']; ?> | Homepage">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo $config['name']; ?>.ovdc.xyz">
    <meta property="og:image" content="<?php echo $config['logo']; ?>">
    <meta property="og:description" content="<?php echo $config['des']; ?>">
    <title><?php echo $config['name']; ?></title>
    <link rel="shortcut icon" href="<?php echo $config['logo']; ?>" type="image/png" sizes="16x16">
    <link rel="stylesheet" href="system/css/second.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js" integrity="sha512-pumBsjNRGGqkPzKHndZMaAG+bir374sORyzM3uulLV14lN5LyykqNk8eEeUlUkB3U0M4FApyaHraT65ihJhDpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <!-- <link rel="stylesheet" href="system/gshake/css/box.css"> -->
    <link href="https://kit-pro.fontawesome.com/releases/v6.2.0/css/pro.min.css" rel="stylesheet">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link rel="stylesheet" href="assets/owl/dist/assets/owl.carousel.min.css"> -->
    <!-- <link rel="stylesheet" href="assets/owl/dist/assets/owl.theme.default.min.css"> -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@600&family=Kanit&display=swap" rel="stylesheet">
    <style>
        :root {
            --main: <?php echo $config["main_color"]; ?>;
            --sub: <?php echo $config["sec_color"]; ?>;
            --sub-opa-50: <?php echo $config["main_color"]; ?>80;
            --sub-opa-25: <?php echo $config["main_color"]; ?>;
        }
    </style>
    <link rel="stylesheet" href="system/css/option.css">
    <style>
        .owl-items {
            max-width: 220px;
            max-height: 220px;

        }

        .owl-items img {
            border-radius: 25px !important;
            animation: glow 2s infinite ease-in-out;
        }
    </style>
</head>

<body style="background-color: #F4FEFF!important;">
    <nav class="navbar navbar-expand-lg navbar-light bg-white mt-0 shadow-sm mb-0">
        <div class="container-sm pt-0 pb-0 ps-4 pe-4 ">
            <a class="navbar-brand" href="/?page=home" ><img src="<?= $config['logo'] ?>" height="80px" width="auto"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <?php
                // if(isset($_SESSION['id'])){
                ?>
                <ul class="navbar-nav me-auto  mb-2 mb-lg-0">
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="/?page=home"><i class="fa-light fa-house-chimney"></i>&nbsp;หน้าหลัก</a>
                    </li>
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="/?page=shop"><i class="fa-regular fa-shopping-basket"></i>&nbsp;สินค้าทั้งหมด</a>
                    </li>
                    <?php if ($byshop_status == "on") : ?>
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="/?page=premiumapp"><i class="fa-regular fa-shopping-basket"></i>&nbsp;แอพพรีเมี่ยม</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="/?page=redeem"><i class="fa-solid fa-code"></i>&nbsp;กรอกโค้ด</a>
                    </li>
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="/?page=topup"><i class="fa-regular fa-coins"></i>&nbsp;เติมเงิน (ซอง)</a>
                    </li>
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="/?page=slip"><i class="fa-regular fa-building-columns"></i>&nbsp;ธนาคาร</a>
                    </li>
                    <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link underline-active align-self-center" aria-current="page" href="<?php echo $config["contact"]; ?>"><i class="fa-regular fa-address-book"></i>&nbsp;ติดต่อเรา</a>
                    </li>
            
                <?php
                if (!isset($_SESSION['id'])) {
                ?>
                    <ul class="navbar-nav  mb-2 mb-lg-0">
                        <li class="nav-item align-self-center ms-lg-3">
                        <a class="nav-link  text-dark" aria-current="page" href="?page=login"><i class="fa-light  fa-right-to-bracket"></i>&nbsp;เข้าสู่ระบบ</a>
                        </li>
                        <li class="nav-item  ms-4  mb-2 align-self-center ">
                            <a class="text-main border-main-gra btn ps-4 pe-4 " style="clip-path: polygon(0 28%, 10% 0, 100% 0%, 100% 68%, 91% 100%, 0% 100%)" aria-current="page" href="?page=register"><i class="fa-light fa-user-plus"></i>&nbsp;สมัครสมาชิก</a>
                        </li>
                    </ul>
                <?php
                } else {
                ?>
                    <ul class="navbar-nav mb-2 mb-lg-0 ">
                        <li class="nav-item ms-3   mb-2 align-self-center">
                            <a class="nav-link  text-dark"><i class="fa-regular fa-coins"></i>&nbsp;Point : <?php echo $user["point"]; ?></a>
                        </li>
                        <li class="nav-item dropdown text-center" style="list-style: none;">
                            <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-circle-user"></i>&nbsp;โปรไฟล์
                            </a>
                            <ul class="dropdown-menu shadow-sm p-4 pt-2 pb-2" style="border-radius: 0px;" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item text-dark mb-1" href="?page=profile"><small><i class="fa-solid fa-user-circle"></i>&nbsp;&nbsp;Username : <?php echo htmlspecialchars($user["username"]); ?></small></a></li>
                                <li><a class="dropdown-item text-dark mb-1" href="?page=profile"><small><i class="fa-regular fa-coins"></i>&nbsp;&nbsp;Point : <?php echo $user["point"]; ?></small></a></li>
                                <li><a class="dropdown-item text-dark mb-1" href="?page=profile"><small><i class="fa-regular fa-coins"></i>&nbsp;&nbsp;ยอดเติมเงินสะสม : <?php echo $user["total"]; ?></small></a></li>
                                <div class="dropdown-divider"></div>
                                <li><a class="dropdown-item text-dark mb-1" href="?page=profile"><small><i class="fa-solid fa-user-circle"></i>&nbsp;&nbsp;ข้อมูลส่วนตัว</small></a></li>
                                <?php
                                if ($user["rank"] == "1") {
                                ?>
                                    <li><a class="dropdown-item text-dark mb-1" href="?page=backend"><small><i class="fa-regular fa-cog"></i>&nbsp;จัดการหลังร้าน</small></a></li>

                                <?php
                                }
                                ?>
                                <li><a class="dropdown-item text-dark mb-1" href="?page=profile&menu=buyhis"><small><i class="fa-solid fa-history"></i>&nbsp;&nbsp;การสั่งซื้อ</small></a></li>
                                <li><a class="dropdown-item text-dark mb-2" href="?page=logout"><small><i class="fa-solid fa-right-from-bracket"></i>&nbsp;&nbsp;ออกจากระบบ</small></a></li>
                            </ul>
                        </li>
                    </ul>
                <?php
                }
                ?>


            </div>
        </div>
    </nav>

    

    <?php
    function admin($user)
    {
        if (isset($_SESSION['id']) && $user["rank"] == "1") {
            return true;
        } else {
            return false;
        }
    }
    if (isset($_GET['page']) && $_GET['page'] == "menu") {
        require_once('page/simple.php');
    } elseif (isset($_GET['page']) && $_GET['page'] == "login" && !isset($_SESSION['id'])) {
        require_once('page/login.php');
    } elseif (isset($_GET['page']) && $_GET['page'] == "logout" && isset($_SESSION['id'])) {
        session_destroy();
        echo "<script>window.location.href = '';</script>";
    } elseif (isset($_GET['page']) && $_GET['page'] == "profile" && isset($_SESSION['id'])) {
        require_once('page/profile.php');
    } elseif (isset($_GET['page']) && $_GET['page'] == "topup") {
        if (isset($_SESSION['id'])) {
            require_once('page/topup.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "redeem") {
        if (isset($_SESSION['id'])) {
            require_once('page/redeem.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "slip") {
        if (isset($_SESSION['id'])) {
            require_once('page/slip.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "id") {
        if (isset($_SESSION['id'])) {
            require_once('page/id.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "gp") {
        if (isset($_SESSION['id'])) {
            require_once('page/gp.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "product" && isset($_GET['id'])) {
        if (isset($_SESSION['id'])) {
            require_once('page/product.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "slidebloxfruit") {
        if (isset($_SESSION['id'])) {
            require_once('page/csgo_1.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "id_p" && isset($_GET['id'])) {
        if (isset($_SESSION['id'])) {
            require_once('page/id_p.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "shop") {
        if (isset($_SESSION['id'])) {
            require_once('page/shop.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "premiumapp") {
        if (isset($_SESSION['id'])) {
            require_once('page/premiumapp.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "buyapp") {
        if (isset($_SESSION['id'])) {
            require_once('page/buyapp.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "my_premiumapp") {
        if (isset($_SESSION['id'])) {
            require_once('page/myapp.php');
        } else {
            require_once('page/login.php');
        }
    } elseif (isset($_GET['page']) && $_GET['page'] == "register" && !isset($_SESSION['id'])) {
        require_once('page/register.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "backend") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "user_edit") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "product_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "stock_manage" && $_GET['id'] != "") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "code_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "category_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "backend_buy_history") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "backend_topup_history") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "carousel_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "recom_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "crecom_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "slip_manage") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "website") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "apibyshop") {
        require_once('page/backend/menu_manage.php');
    } elseif (admin($user) && isset($_GET['page']) && $_GET['page'] == "apibyshop_his") {
        require_once('page/backend/menu_manage.php');
    } else {
        require_once('page/simple.php');
    }
    ?>
    <div class="modal fade" id="buy_count" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_title"><i class="fa-duotone fa-cart-shopping-fast"></i>&nbsp;&nbsp;สั่งซื้อสินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 pb-2">
                    <div class="mb-2">
                        <p class="mb-1 text-secondary">กรอกจำนวนที่ต้องการสั่งซื้อ<span class="text-danger">*</span></p>
                        <input type="number" id="b_count" class="form-control text-center" value="1">
                    </div>
                    <div class="d-flex justify-content-between pe-3 ps-3 mt-2">
                        <span class="m-0 align-self-center">มีสต๊อกคงเหลือ <span id="s">0</span> ชิ้น </span>
                        <span class="m-0" style="color: white; padding: 3.5px 5px; border-radius: 1vh; background-color: var(--main);"><b><span id="b">0</span> ฿</b></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="shop-btn" class="btn w-100" style="background-color: var(--main); color: #fff;" onclick="buybox()" data-id="" data-name=""><i class="text-black fa-duotone fa-cart-shopping-fast"></i>&nbsp;&nbsp;สั่งซื้้อเลย</button>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-white shadow pt-2 pb-2 mt-3">
        <center>
            <p class="text-dark mb-1"><strong><i class="fa-regular fa-copyright"></i>&nbsp; 2024 <a > ZerxCloud</a> , All right reserved.</strong></p>
            <p class="text-dark mb-1"><strong>Icon By</i><a href="https://www.fontawesome.com/" class="text-dark"> Fontawesome </a> <a><strong>&</strong></a> <a href="https://www.flaticon.com/" class="text-dark"> Flaticon</a></strong></p>
            <p><strong><i class="fa-solid fa-code fa-spin"></i> Powered By Phattadol</strong></p>
        </center>
    </div>
    <!-- <script src="system/gshake/gshake.js"></script> -->
    <!-- <script src="assets/owl/dist/owl.carousel.min.js"></script> -->
    <!-- <script>
        $(document).ready(function() {
            $(".owl-carousel").owlCarousel({
                items: 4,
                margin: 40,
                autoplay: true,
                autoplayTimeout: 1300,
                loop: true
            });
        });
    </script> -->
    <script>
        function tobuy(id, name, s, b) {
            $("#modal_title").html('<i class="fa-duotone fa-cart-shopping-fast"></i>&nbsp;&nbsp;' + name);
            $("#shop-btn").attr("data-id", id);
            $("#shop-btn").attr("data-name", name);
            $("#s").html(s);
            $("#b").html(b);
            const myModal = new bootstrap.Modal('#buy_count ', {
                keyboard: false
            })
            myModal.show();
        }

        function detail(id) {
            var formData = new FormData();
            formData.append('id', id);

            $.ajax({
                type: 'POST',
                url: 'system/call/product_detail.php',
                data: formData,
                contentType: false,
                processData: false,
            }).done(function(res) {
                $("#p_img").attr("src", res.img);
                $("#p_name").html(res.name);
                $("#p_des").html(res.des);
                const myModal = new bootstrap.Modal('#product_detail', {
                    keyboard: false
                })
                myModal.show();
            }).fail(function(jqXHR) {
                console.log(jqXHR);
                res = jqXHR.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: res.message
                })
                //console.clear();
            });
        }
    </script>
    <script>
        async function shake_alert(status, result) {
            if (status) {
                if (result.salt == "prize") {
                    // await GShake();
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: result.message
                    }).then(function() {
                        window.location = "?page=profile&subpage=buyhis";
                    });
                } else {
                    await GShake();
                    Swal.fire({
                        icon: 'error',
                        title: 'เสียใจด้วย',
                        text: result.message
                    });
                }
            } else {
                if (result.salt == "salt") {
                    // await GShake();
                    Swal.fire({
                        icon: 'error',
                        title: 'เสียใจด้วย',
                        text: result.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: result.message
                    });
                }
            }
        }

        function buybox() {
            var name = $("#shop-btn").attr("data-name");
            var formData = new FormData();
            formData.append('id', $("#shop-btn").attr("data-id"));
            formData.append('count', $("#b_count").val());
            Swal.fire({
                title: 'ยืนยันการสั่งซื้อ?',
                text: "ยืนยันที่จะซื้อ " + name + " หรือไม่",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ซื้อเลย'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: 'system/buybox.php',
                        data: formData,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $('#btn_buyid').attr('disabled', 'disabled');
                            $('#btn_buyid').html('<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>รอสักครู่...');
                        },
                    }).done(function(res) {
                        console.log(res)
                        result = res;
                        // await GShake();
                        shake_alert(true, result);
                        console.clear();
                        $('#btn_buyid').html('<i class="fas fa-shopping-cart mr-1"></i>สั่งซื้อสินค้า');
                        $('#btn_buyid').removeAttr('disabled');
                    }).fail(function(jqXHR) {
                        console.log(jqXHR)
                        res = jqXHR.responseJSON;
                        shake_alert(false, res);

                        $('#btn_buyid').html('<i class="fas fa-shopping-cart mr-1"></i>สั่งซื้อสินค้า');
                        $('#btn_buyid').removeAttr('disabled');
                    });
                }
            })
        }
    </script>
    <script>
        AOS.init();
        // var options = {
        //     strings: [`<?php //echo $s_info['des']; ?>`],
        //     typeSpeed: 40,
        //     color: "#fff"
        // };
        // var typed = new Typed('#typing', options);
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Dora-Literature: Cuộc Phiêu Lưu Văn Học</title>

        <!-- Tải Tailwind CSS -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Tải font Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet" />

        <style>
            /* Áp dụng font Inter cho toàn bộ trang */
            body {
                font-family: 'Inter', sans-serif;
                /* Đảm bảo cuộn mượt mà khi nhấp vào link neo */
                scroll-behavior: smooth;
            }
            /* Hiệu ứng mượt mà cho menu mobile */
            #mobile-menu {
                transition: transform 0.3s ease-in-out;
            }

            /* Lớp CSS cho hiệu ứng Fade-in khi cuộn */
            .fade-in-section {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            }
            .fade-in-section.is-visible {
                opacity: 1;
                transform: translateY(0);
            }

            /* Thêm keyframes cho hiệu ứng nhấp nhô */
            @keyframes float {
                0% {
                    transform: translateY(0px);
                }
                50% {
                    transform: translateY(-8px);
                }
                100% {
                    transform: translateY(0px);
                }
            }

            /* Áp dụng hiệu ứng nhấp nhô khi hover cho các thẻ */
            .card-float-effect:hover {
                animation: float 0.8s ease-in-out infinite;
            }

            /* Hiệu ứng nhấn xuống cho button */
            .button-press-effect:active {
                transform: scale(0.98);
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>
    <body class="bg-blue-50">
        <!-- =========== Header / Thanh Điều Hướng =========== -->
        <header class="bg-white shadow-md fixed w-full top-0 z-50">
            <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
                <!-- Logo --><a href="#" class="text-3xl font-bold text-blue-600 flex items-center gap-2">
                    <img src="/views/Landing/photo/doraemon-icon-1.jpg" alt="Doraemon book icon" class="w-10 h-10" />
                    <!-- Icon sách cute -->Dora-Literature
                </a>

                <!-- Menu cho Desktop (ĐÃ CẬP NHẬT) -->
                <div class="hidden md:flex space-x-6 items-center">
                    <a href="#hero" class="text-gray-600 hover:text-blue-500 transition duration-300">Trang chủ</a>
                    <a href="#features" class="text-gray-600 hover:text-blue-500 transition duration-300">Tính năng</a>
                    <a href="#benefits" class="text-gray-600 hover:text-blue-500 transition duration-300">Lợi ích</a>
                    <a href="#collection" class="text-gray-600 hover:text-blue-500 transition duration-300">Bộ sưu tập</a>
                    <a href="#genres" class="text-gray-600 hover:text-blue-500 transition duration-300">Thể loại</a>
                    <a href="#about" class="text-gray-600 hover:text-blue-500 transition duration-300">Về chúng tôi</a>
                    <a href="#testimonials" class="text-gray-600 hover:text-blue-500 transition duration-300">Đánh giá</a>

                    <!-- Nút Đăng Nhập (Theo yêu cầu) -->
                    <?php if (!isset($_SESSION['user'])): ?>
                    <button
                        id="login-button"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300 transform hover:scale-105 button-press-effect">
                        Đăng nhập
                    </button>
                    <?php elseif (isset($_SESSION['user'])): ?>
                        <button class="hidden" id="login-button"></button>
                        <a href="login" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300 transform hover:scale-105 button-press-effect">
                            Vào hệ thống
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Nút Menu cho Mobile -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-700 focus:outline-none" aria-label="Menu">
                        <svg
                            class="w-7 h-7"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </nav>

            <!-- Menu thả xuống cho Mobile (ĐÃ CẬP NHẬT) -->
            <div id="mobile-menu" class="md:hidden hidden absolute top-full left-0 w-full bg-white shadow-lg py-4">
                <a href="#hero" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Trang chủ</a>
                <a href="#features" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Tính năng</a>
                <a href="#benefits" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Lợi ích</a>
                <a href="#collection" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Bộ sưu tập</a>
                <a href="#genres" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Thể loại</a>
                <a href="#about" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Về chúng tôi</a>
                <a href="#testimonials" class="block text-center px-4 py-3 text-gray-700 hover:bg-blue-50">Đánh giá</a>
                <div class="px-6 mt-2">
                    <button
                        id="login-button-mobile"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300 button-press-effect">
                        Đăng nhập
                    </button>
                </div>
            </div>
        </header>

        <!-- =========== Phần Hero =========== -->
        <section id="hero" class="pt-24 md:pt-32 pb-20 bg-gradient-to-r from-blue-100 to-sky-200 fade-in-section">
            <div class="container mx-auto px-6 flex flex-col md:flex-row items-center gap-12">
                <!-- Nội dung text bên trái -->
                <div class="md:w-1/2 text-center md:text-left">
                    <h1 class="text-4xl md:text-6xl font-extrabold text-gray-800 mb-6 leading-tight">
                        Mở Cửa Thần Kỳ
                        <span class="text-blue-600">Bước Vào Thế Giới Văn Học</span>
                    </h1>
                    <p class="text-lg text-gray-600 mb-10">
                        Cùng Doraemon và những người bạn, khám phá hàng ngàn câu chuyện kỳ thú, những tác phẩm kinh điển và bồi đắp tình yêu đọc sách
                        mỗi ngày.
                    </p>
                    <a
                        href="#collection"
                        class="bg-yellow-400 hover:bg-yellow-500 text-gray-800 font-bold py-4 px-10 rounded-full shadow-lg transition duration-300 transform hover:scale-105 button-press-effect">
                        Khám Phá Ngay
                    </a>
                </div>

                <!-- Hình ảnh bên phải -->
                <div class="md:w-1/2">
                    <img
                        src="/views/Landing/photo/dora-read.jpg"
                        alt="Doraemon và những cuốn sách văn học"
                        class="w-full h-auto rounded-xl shadow-2xl transform rotate-3 hover:rotate-0 transition-transform duration-500"
                        onerror="this.src='https://placehold.co/600x450/A7F3D0/10B981/png?text=L%E1%BB%97i+T%E1%BA%A3i+H%C3%ACnh'" />
                </div>
            </div>
        </section>

        <!-- =========== Phần Tính Năng (Bảo Bối) =========== -->
        <section id="features" class="py-24 bg-white fade-in-section">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-6">Những 'Bảo Bối' Văn Học</h2>
                <p class="text-lg text-gray-600 text-center mb-16 max-w-2xl mx-auto">
                    Chúng tôi biến việc đọc sách thành một cuộc phiêu lưu với những công cụ thú vị, lấy cảm hứng từ thế giới của Doraemon.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Tính năng 1: Cánh Cửa Thần Kỳ -->
                    <div
                        class="bg-blue-50 p-8 rounded-xl shadow-lg border border-blue-100 text-center transform transition duration-300 hover:shadow-xl card-float-effect">
                        <!-- Icon SVG: Cửa --><img
                            src="/views/Landing/photo/canh-cua-than-ky-2.jpg"
                            alt="Magic Door Icon"
                            class="w-16 h-16 text-blue-500 mx-auto mb-6" />
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Cánh Cửa Thần Kỳ</h3>
                        <p class="text-gray-600">
                            Du hành đến bất kỳ thế giới văn học nào chỉ với một cú nhấp chuột. Từ cổ tích đến viễn tưởng, mọi thể loại đều có sẵn.
                        </p>
                    </div>

                    <!-- Tính năng 2: Cỗ Máy Thời Gian -->
                    <div
                        class="bg-yellow-50 p-8 rounded-xl shadow-lg border border-yellow-100 text-center transform transition duration-300 hover:shadow-xl card-float-effect">
                        <!-- Icon SVG: Đồng hồ --><img
                            src="/views/Landing/photo/time-traveler-3.jpg"
                            alt="Time Machine Icon"
                            class="w-16 h-16 text-yellow-500 mx-auto mb-6" />
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Cỗ Máy Thời Gian</h3>
                        <p class="text-gray-600">
                            Khám phá những tác phẩm kinh điển vượt thời gian. Đọc các tác phẩm văn học đã định hình nên các thế hệ.
                        </p>
                    </div>

                    <!-- Tính năng 3: Bánh Mì Giúp Trí Nhớ -->
                    <div
                        class="bg-green-50 p-8 rounded-xl shadow-lg border border-green-100 text-center transform transition duration-300 hover:shadow-xl card-float-effect">
                        <!-- Icon SVG: Bánh mì --><img
                            src="/views/Landing/photo/banh_mi_tri_nho.jpg"
                            alt="Bread Icon"
                            class="w-16 h-16 text-green-500 mx-auto mb-6" />
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Bánh Mì Giúp Trí Nhớ</h3>
                        <p class="text-gray-600">
                            Hệ thống ghi chú và đánh dấu thông minh giúp bạn lưu lại những trích dẫn hay và ý tưởng quan trọng từ sách.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========== [MỤC MỚI] Lợi Ích Của Việc Đọc =========== -->
        <section id="benefits" class="py-24 bg-blue-100 fade-in-section">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-6">Tại Sao Văn Học Lại Quan Trọng?</h2>
                <p class="text-lg text-gray-600 text-center mb-16 max-w-2xl mx-auto">
                    Đọc không chỉ là giải trí. Đó là hành trình bồi đắp tâm hồn và trí tuệ, là 'bảo bối' vĩ đại nhất của con người.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Lợi ích 1 -->
                    <div
                        class="bg-white p-8 rounded-xl shadow-lg border border-blue-200 text-center transform transition duration-300 hover:shadow-xl card-float-effect">
                        <!-- Icon SVG: Sách Mở --><img
                            src="/views/Landing/photo/book-svgrepo-com.svg"
                            alt="Open Book Icon"
                            class="w-16 h-16 text-blue-600 mx-auto mb-6" />
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Mở Rộng Tri Thức</h3>
                        <p class="text-gray-600">
                            Khám phá những nền văn hóa mới, những tư tưởng mới và hiểu biết sâu sắc hơn về thế giới xung quanh bạn.
                        </p>
                    </div>

                    <!-- Lợi ích 2 -->
                    <div
                        class="bg-white p-8 rounded-xl shadow-lg border border-pink-200 text-center transform transition duration-300 hover:shadow-xl card-float-effect">
                        <!-- Icon SVG: Trái tim cảm xúc --><img
                            src="/views/Landing/photo/heart-pulse-svgrepo-com.svg"
                            alt="Heart Icon"
                            class="w-16 h-16 text-pink-500 mx-auto mb-6" />
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Tăng Cường Thấu Cảm</h3>
                        <p class="text-gray-600">
                            Đặt mình vào vị trí của nhân vật, trải nghiệm cuộc sống qua lăng kính của người khác và phát triển lòng trắc ẩn.
                        </p>
                    </div>

                    <!-- Lợi ích 3 -->
                    <div
                        class="bg-white p-8 rounded-xl shadow-lg border border-yellow-200 text-center transform transition duration-300 hover:shadow-xl card-float-effect">
                        <!-- Icon SVG: Bóng đèn (Tư duy) --><img
                            src="/views/Landing/photo/bulb-education-idea-svgrepo-com.svg"
                            alt="Bulb Icon"
                            class="w-16 h-16 text-yellow-600 mx-auto mb-6" />
                        <h3 class="text-2xl font-semibold text-gray-800 mb-3">Rèn Luyện Tư Duy</h3>
                        <p class="text-gray-600">
                            Văn học thử thách chúng ta phân tích các tình huống phức tạp, đặt câu hỏi và mài giũa khả năng tư duy phản biện.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========== Phần Giới Thiệu (Về chúng tôi) =========== -->
        <section id="about" class="py-24 bg-gradient-to-br from-white to-blue-50 fade-in-section">
            <div class="container mx-auto px-6 flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2">
                    <img
                        src="/views/Landing/photo/nobi-dora.png"
                        alt="Doraemon và Nobita đọc sách"
                        class="w-full h-auto rounded-full shadow-xl animate-pulse-slow"
                        onerror="this.src='https://placehold.co/500x500/FDBA74/FFFFFF?text=L%E1%BB%97i+T%E1%BA%A3i+H%C3%ACnh'" />
                </div>
                <div class="md:w-1/2 text-center md:text-left">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">Không Chỉ Là Những Câu Chuyện</h2>
                    <p class="text-lg text-gray-700 leading-relaxed mb-4">
                        Giống như Doraemon tin vào sức mạnh của tình bạn, lòng dũng cảm và trí tưởng tượng, Dora-Literature tin rằng văn học là 'bảo
                        bối' vĩ đại nhất để mở mang trí óc và nuôi dưỡng tâm hồn.
                    </p>
                    <p class="text-lg text-gray-700 leading-relaxed">
                        Sứ mệnh của chúng tôi là làm cho văn học trở nên dễ tiếp cận, thú vị và ý nghĩa đối với độc giả ở mọi lứa tuổi, khơi dậy tinh
                        thần phiêu lưu như của Nobita trong mỗi chúng ta.
                    </p>
                </div>
            </div>
        </section>

        <!-- =========== Bộ Sưu Tập Nổi Bật =========== -->
        <section id="collection" class="py-24 bg-white fade-in-section">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-16">Bộ Sưu Tập Nổi Bật</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <!-- Sách 1 -->
                    <div
                        class="bg-white rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl card-float-effect">
                        <img
                            src="https://placehold.co/300x400/818CF8/FFFFFF?text=Kinh+Điển"
                            alt="Bìa sách Văn học Kinh điển"
                            class="w-full h-64 object-cover"
                            onerror="this.src='https://placehold.co/300x400/818CF8/FFFFFF?text=H%C3%ACnh+S%C3%A1ch'" />
                        <div class="p-5 text-center">
                            <h3 class="text-xl font-semibold text-gray-800">Văn Học Kinh Điển</h3>
                            <p class="text-gray-500 mt-1">Những tác phẩm vĩ đại</p>
                        </div>
                    </div>

                    <!-- Sách 2 -->
                    <div
                        class="bg-white rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl card-float-effect">
                        <img
                            src="https://placehold.co/300x400/F472B6/FFFFFF?text=Hi%E1%BB%87n+%C4%90%E1%BA%A1i"
                            alt="Bìa sách Văn học Hiện đại"
                            class="w-full h-64 object-cover"
                            onerror="this.src='https://placehold.co/300x400/F472B6/FFFFFF?text=H%C3%ACnh+S%C3%A1ch'" />
                        <div class="p-5 text-center">
                            <h3 class="text-xl font-semibold text-gray-800">Văn Học Hiện Đại</h3>
                            <p class="text-gray-500 mt-1">Câu chuyện của thời đại</p>
                        </div>
                    </div>

                    <!-- Sách 3 -->
                    <div
                        class="bg-white rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl card-float-effect">
                        <img
                            src="https://placehold.co/300x400/34D399/FFFFFF?text=Phi%C3%AAu+L%C6%B0u"
                            alt="Bìa sách Phiêu Lưu Ký"
                            class="w-full h-64 object-cover"
                            onerror="this.src='https://placehold.co/300x400/34D399/FFFFFF?text=H%C3%ACnh+S%C3%A1ch'" />
                        <div class="p-5 text-center">
                            <h3 class="text-xl font-semibold text-gray-800">Phiêu Lưu Ký</h3>
                            <p class="text-gray-500 mt-1">Những chuyến đi kỳ thú</p>
                        </div>
                    </div>

                    <!-- Sách 4 -->
                    <div
                        class="bg-white rounded-lg shadow-md overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl card-float-effect">
                        <img
                            src="https://placehold.co/300x400/FBBF24/FFFFFF?text=Th%C6%A1+Ca"
                            alt="Bìa sách Thơ Ca Tuyển"
                            class="w-full h-64 object-cover"
                            onerror="this.src='https://placehold.co/300x400/FBBF24/FFFFFF?text=H%C3%ACnh+S%C3%A1ch'" />
                        <div class="p-5 text-center">
                            <h3 class="text-xl font-semibold text-gray-800">Thơ Ca Tuyển</h3>
                            <p class="text-gray-500 mt-1">Cảm xúc và vần điệu</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========== [MỤC MỚI] Khám Phá Thể Loại =========== -->
        <section id="genres" class="py-24 bg-blue-100 fade-in-section">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-6">Khám Phá Mọi Thể Loại Văn Học</h2>
                <p class="text-lg text-gray-600 text-center mb-16 max-w-2xl mx-auto">
                    Từ những vần thơ lãng mạn đến những vụ án phức tạp, thư viện của chúng tôi có mọi thứ bạn cần.
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-pink-100 text-pink-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Lãng Mạn</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-gray-100 text-gray-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Trinh Thám</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-purple-100 text-purple-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.82m5.84-2.56a16.5 16.5 0 0 0-1.63-7.24m-1.63 7.24A16.5 16.5 0 0 1 12 2.83m0 18.34A16.5 16.5 0 0 0 8.37 7.13m5.84 7.24a16.49 16.49 0 0 1-5.84 0M12 21a16.5 16.5 0 0 1-8.37-2.56m16.74 0a16.5 16.5 0 0 0-8.37 2.56" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Viễn Tưởng</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-orange-100 text-orange-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Lịch Sử</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-cyan-100 text-cyan-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-5.25 6.557v3.675a55.378 55.378 0 0 0 5.25 2.102" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Kịch</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-yellow-100 text-yellow-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Thơ Ca</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-indigo-100 text-indigo-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125v-1.5c0-.621.504-1.125 1.125-1.125H5.25m-1.875 3.75h.008v.008h-.008v-.008Zm0 0H19.5m0 0h1.125c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-1.875m-3.75 3.75h.008v.008h-.008v-.008Zm0 0H12.125m0 0v-3.75m0 3.75c0 .621.504 1.125 1.125 1.125h1.125c.621 0 1.125-.504 1.125-1.125v-3.75m-3.75 0H8.625m3.5 0v-3.75M8.625 12v3.75M3.375 12H5.25m-1.875 0h.008v.008h-.008V12Zm0 0v-3.75m0 3.75c0 .621.504 1.125 1.125 1.125h1.125c.621 0 1.125-.504 1.125-1.125V8.25m-3.75 0H8.625m3.5 0v-3.75M8.625 4.5v3.75m3.5-3.75H12.125m0 0V8.25m0 0H15.375m0 0v-3.75m0 3.75c0 .621.504 1.125 1.125 1.125h1.125c.621 0 1.125-.504 1.125-1.125V4.5m-3.75 0h.008v.008h-.008V4.5Zm0 0H19.5m0 0h1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125h-1.875m-1.875-3.75h.008v.008h-.008V4.5Z" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Tản Văn</h3>
                    </a>

                    <a
                        href="#"
                        class="bg-white p-6 rounded-lg shadow-md flex items-center gap-4 transition duration-300 hover:shadow-xl hover:scale-105 card-float-effect">
                        <span class="bg-green-100 text-green-700 p-3 rounded-full">
                            <svg
                                class="w-6 h-6"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125M12 15v5.25m0 0H9m3 0h3m-3 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                            </svg>
                        </span>
                        <h3 class="text-xl font-semibold text-gray-800">Truyện ngắn</h3>
                    </a>
                </div>
            </div>
        </section>

        <!-- =========== Nguồn Cảm Hứng Bất Tận =========== -->
        <section id="testimonials" class="py-24 bg-gradient-to-br from-white to-gray-100 fade-in-section">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800 mb-16">Nguồn Cảm Hứng Bất Tận</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Đánh giá 1: Nam Cao -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 card-float-effect">
                        <img
                            src="/views/Landing/photo/logo-nam-cao.jpg"
                            alt="Nam Cao"
                            class="w-24 h-24 rounded-full mx-auto -mt-16 mb-6 border-4 border-white shadow-md" />
                        <blockquote class="text-lg text-gray-600 italic text-center mb-6 h-40 flex items-center justify-center">
                            "Văn chương... chỉ dung nạp những người biết đào sâu, biết tìm tòi, khơi những nguồn chưa ai khơi, và sáng tạo những cái
                            gì chưa có..."
                        </blockquote>
                        <p class="text-xl font-semibold text-center text-gray-800">Nam Cao</p>
                        <p class="text-center text-gray-500">Tác giả "Chí Phèo", "Lão Hạc"</p>
                    </div>

                    <!-- Đánh giá 2: Tô Hoài -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 card-float-effect">
                        <img
                            src="/views/Landing/photo/to-hoai.jpg"
                            alt="Tô Hoài"
                            class="w-24 h-24 rounded-full mx-auto -mt-16 mb-6 border-4 border-white shadow-md" />
                        <blockquote class="text-lg text-gray-600 italic text-center mb-6 h-40 flex items-center justify-center">
                            "Cuộc đời là những chuyến phiêu lưu bất tận. Mỗi cuốn sách là một vé tàu đưa ta đến những vùng đất mới, gặp những con
                            người mới."
                        </blockquote>
                        <p class="text-xl font-semibold text-center text-gray-800">Tô Hoài</p>
                        <p class="text-center text-gray-500">Tác giả "Dế Mèn Phiêu Lưu Ký"</p>
                    </div>

                    <!-- Đánh giá 3: Nguyễn Du -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 card-float-effect">
                        <img
                            src="/views/Landing/photo/nguyen-du.jpg"
                            alt="Nguyễn Du"
                            class="w-24 h-24 rounded-full mx-auto -mt-16 mb-6 border-4 border-white shadow-md" />
                        <blockquote class="text-lg text-gray-600 italic text-center mb-6 h-40 flex items-center justify-center">
                            "Văn chương không chỉ là chữ nghĩa, mà là tiếng lòng của cả một dân tộc, là hành trình khám phá tâm hồn con người."
                        </blockquote>
                        <p class="text-xl font-semibold text-center text-gray-800">Nguyễn Du</p>
                        <p class="text-center text-gray-500">Đại thi hào, Tác giả "Truyện Kiều"</p>
                    </div>

                    <!-- Đánh giá 4: Xuân Diệu -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 card-float-effect">
                        <img
                            src="/views/Landing/photo/XuanDieu.jpg"
                            alt="Xuân Diệu"
                            class="w-24 h-24 rounded-full mx-auto -mt-16 mb-6 border-4 border-white shadow-md" />
                        <blockquote class="text-lg text-gray-600 italic text-center mb-6 h-40 flex items-center justify-center">
                            "Thà một phút huy hoàng rồi chợt tối / Còn hơn buồn le lói suốt trăm năm."
                        </blockquote>
                        <p class="text-xl font-semibold text-center text-gray-800">Xuân Diệu</p>
                        <p class="text-center text-gray-500">"Ông hoàng" thơ tình Việt Nam</p>
                    </div>

                    <!-- Đánh giá 5: Hàn Mặc Tử -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 card-float-effect">
                        <img
                            src="/views/Landing/photo/HanMacTu.jpeg"
                            alt="Hàn Mặc Tử"
                            class="w-24 h-24 rounded-full mx-auto -mt-16 mb-6 border-4 border-white shadow-md" />
                        <blockquote class="text-lg text-gray-600 italic text-center mb-6 h-40 flex items-center justify-center">
                            "Sao trăng lọt qua kẽ lá / Nửa đêm trăng rụng xuống cành cây..."
                        </blockquote>
                        <p class="text-xl font-semibold text-center text-gray-800">Hàn Mặc Tử</p>
                        <p class="text-center text-gray-500">Thi sĩ của "Đau thương" và "Trăng"</p>
                    </div>

                    <!-- Đánh giá 6: Nguyễn Bính -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 card-float-effect">
                        <img
                            src="/views/Landing/photo/NguyenBinh.jpg"
                            alt="Nguyễn Bính"
                            class="w-24 h-24 rounded-full mx-auto -mt-16 mb-6 border-4 border-white shadow-md" />
                        <blockquote class="text-lg text-gray-600 italic text-center mb-6 h-40 flex items-center justify-center">
                            "Thôn Đoài ngồi nhớ thôn Đông, / Một người chín nhớ mười mong một người."
                        </blockquote>
                        <p class="text-xl font-semibold text-center text-gray-800">Nguyễn Bính</p>
                        <p class="text-center text-gray-500">Thi sĩ của "Lỡ bước sang ngang"</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========== Phần Kêu Gọi Hành Động (CTA) Cuối Cùng =========== -->
        <section id="final-cta" class="py-24 bg-gradient-to-r from-blue-600 to-blue-800 fade-in-section">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-4xl font-extrabold text-white mb-6">Sẵn Sàng Cho Cuộc Phiêu Lưu Của Riêng Bạn?</h2>
                <p class="text-lg text-blue-100 mb-10 max-w-xl mx-auto">
                    Tạo tài khoản miễn phí và bắt đầu hành trình văn học của bạn ngay hôm nay. Hàng ngàn câu chuyện đang chờ bạn khám phá!
                </p>
                <button
                    class="bg-yellow-400 hover:bg-yellow-300 text-gray-800 font-bold py-4 px-10 rounded-full shadow-lg transition duration-300 transform hover:scale-105 button-press-effect">
                    Đăng Ký Miễn Phí
                </button>
            </div>
        </section>

        <!-- =========== Footer =========== -->
        <footer class="py-12 bg-gray-800 text-gray-400">
            <div class="container mx-auto px-6 text-center">
                <p class="text-lg mb-4 flex items-center justify-center gap-2">
                    <img src="/views/Landing/photo/dora.jpg" alt="Doraemon icon" class="w-8 h-8 text-blue-400" />
                    Dora-Literature
                </p>
                <p class="mb-4">
                    <a href="#" class="mx-3 hover:text-white">Chính sách bảo mật</a> |
                    <a href="#" class="mx-3 hover:text-white">Điều khoản sử dụng</a> |
                    <a href="#" class="mx-3 hover:text-white">Liên hệ</a>
                </p>
                <p>&copy; 2025 Dora-Literature</p>
            </div>
        </footer>

        <!-- =========== [NÂNG CẤP] Nút Cuộn Lên Đầu Trang =========== -->
        <button
            id="scroll-to-top-button"
            class="hidden fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition-opacity duration-300 z-40 button-press-effect"
            aria-label="Cuộn lên đầu trang">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
            </svg>
        </button>

        <!-- =========== JavaScript =========== -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Xử lý Menu Mobile
                const mobileMenuButton = document.getElementById('mobile-menu-button');
                const mobileMenu = document.getElementById('mobile-menu');

                mobileMenuButton.addEventListener('click', () => {
                    mobileMenu.classList.toggle('hidden');
                });

                // Xử lý Nút Đăng Nhập
                const loginButton = document.getElementById('login-button');
                const loginButtonMobile = document.getElementById('login-button-mobile');

                loginButton.addEventListener('click', () => {
                    window.location.href = '/login';
                });

                loginButtonMobile.addEventListener('click', () => {
                    window.location.href = '/login';
                });

                // --- [NÂNG CẤP] Logic Nút Cuộn Lên Đầu Trang ---
                const scrollToTopButton = document.getElementById('scroll-to-top-button');

                window.addEventListener('scroll', () => {
                    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                        scrollToTopButton.classList.remove('hidden');
                    } else {
                        scrollToTopButton.classList.add('hidden');
                    }
                });

                scrollToTopButton.addEventListener('click', () => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth',
                    });
                });

                // --- [NÂNG CẤP] Logic Hiệu Ứng Fade-in khi Cuộn ---
                const sections = document.querySelectorAll('.fade-in-section');

                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver(
                        (entries, observerInstance) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    entry.target.classList.add('is-visible');
                                    observerInstance.unobserve(entry.target); // Chỉ chạy 1 lần
                                }
                            });
                        },
                        {
                            threshold: 0.1, // Kích hoạt khi 10% phần tử xuất hiện
                        },
                    );

                    sections.forEach((section) => {
                        observer.observe(section);
                    });
                } else {
                    // Fallback cho trình duyệt cũ không hỗ trợ IntersectionObserver
                    sections.forEach((section) => {
                        section.classList.add('is-visible');
                    });
                }
            });
        </script>
    </body>
</html>

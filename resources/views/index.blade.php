<!doctype html>
<html lang="zxx" class="theme-light">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS --> 
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <!-- Owl Default CSS --> 
        <link rel="stylesheet" href="assets/css/owl.default.min.css">
        <!-- Owl Carousel CSS --> 
        <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
        <!-- Owl Magnific CSS --> 
        <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
        <!-- Animate CSS --> 
        <link rel="stylesheet" href="assets/css/animate.min.css">
        <!-- Boxicons CSS --> 
		<link rel="stylesheet" href="assets/css/boxicons.min.css">
        <!-- Flaticon CSS --> 
		<link rel="stylesheet" href="assets/css/flaticon.css">
        <!-- Meanmenu CSS -->
        <link rel="stylesheet" href="assets/css/meanmenu.css">
		<!-- Odometer CSS-->
		<link rel="stylesheet" href="assets/css/odometer.min.css">
        <!-- Style CSS -->
        <link rel="stylesheet" href="assets/css/style.css">
        <!-- RTL CSS -->
        <link rel="stylesheet" href="assets/css/dark.css">
        <!-- Responsive CSS -->
		<link rel="stylesheet" href="assets/css/responsive.css">
		
        <title>
            {{ $general->app_name }} | A technology platform that offers solutions to digital needs at best possible
            price without
            compromising quality. data, airtime, electricity, cable, airtime to cash, all available for you...
        </title>
    
        <link rel="icon" type="image/png" href="img/logo-image.png">
    </head>

    <body>

       

            <!-- Start Navbar Area -->
            <div class="navbar-area navbar-two">
                <div class="fria-responsive-nav">
                    <div class="container">
                        <div class="fria-responsive-menu">
                            <div class="logo">
                                <a>
                                    <img src="img/logo-image.png" style="height: 50px" alt="Logo">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="fria-nav">
                    <div class="container">
                        <nav class="navbar navbar-expand-md navbar-light">
                            <a class="navbar-brand">
                                <img src="img/logo-image.png" style="height: 50px" alt="Logo">
                            </a>
            
                            <div class="collapse navbar-collapse mean-menu" id="navbarSupportedContent">
                                <ul class="navbar-nav">
                                    <li class="nav-item">
                                        <a href="#home" class="nav-link active">
                                            Home
                                        </a>
                                       
                                    </li>
            
                                    <li class="nav-item">
                                        <a href="#service" class="nav-link">
                                            Services 
                                        </a>
                                    </li>
            
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            Authentication
                                            <i class='bx bx-chevron-down'></i>
                                        </a>
            
                                        <ul class="dropdown-menu">
                                        
                                            <li class="nav-item">
                                                <a href="{{ env('APP_URL') . '/auth/login' }}" class="nav-link">
                                                    Log In
                                                </a>
                                            </li>
            
                                            <li class="nav-item">
                                                <a href="{{ env('APP_URL') . '/auth/register' }}" class="nav-link">
                                                    Sign Up
                                                </a>
                                            </li>
            
                                            <li class="nav-item">
                                                <a href="{{ env('APP_URL') . '/auth/passwords/reset' }}" class="nav-link">
                                                    Recover Password
                                                </a>
                                            </li>
                                              
                                        </ul>
                                    </li>
            
                                    <li class="nav-item">
                                        <a href="#pricing" class="nav-link">
                                            Pricing
                                        </a>
                                    </li>
            
                                    <li class="nav-item">
                                        <a href="#faq" class="nav-link">
                                            FAQ
                                        </a>
                                    </li>
            
                                    <li class="nav-item">
                                        <a href="#contact" class="nav-link">
                                            Contact
                                        </a>
                                    </li>
                                </ul>
            
                                <div class="others-options">
                                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="default-btn">Get Started</a>
                                </div>
            
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
            <!-- End Navbar Area -->
            

        <!-- Start Banner Area -->
        <div id="home" class="main-banner-area-five">
            <div class="d-table">
                <div class="d-table-cell">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="main-banner-content">
                                    <span style="color: blue">Welcome to {{ $general->app_name }}</span>
                                    <h1>Buy Internet Data At A Cheaper Rate</h1>
                                    <p>
                                        Here at {{ $general->app_name }}, we offer you the most affordable and most cheapest data, airtime, Dstv, Gotv and 
                                        Startimes subscription. Here is the right place for your Electricity subscription and also Convert your Airtime to 
                                        Cash...
                                      </p>
                                      <div class="banner-btn">
                                        <a class="default-btn" href="{{ env('APP_URL') . '/auth/register' }}">Create Free Account</a>
                                        <a class="default-btn" href="{{ env('APP_URL') . '/auth/login' }}">Merchant Login</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="banner-image">
                                    <img src="img/happy-dread-phone.png" alt="image">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="default-shape">
                <div class="shape-1">
                    <img src="assets/img/shape/4.png" alt="image">
                </div>

                <div class="shape-2 rotateme">
                    <img src="assets/img/shape/5.svg" alt="image">
                </div>

                <div class="shape-3">
                    <img src="assets/img/shape/6.svg" alt="image">
                </div>

                <div class="shape-4">
                    <img src="assets/img/shape/7.png" alt="image">
                </div>

                <div class="shape-5">
                    <img src="assets/img/shape/8.png" alt="image">
                </div>
            </div>

            <div class="banner-shape">
                <img src="assets/img/home-five/shape.png" alt="image">
            </div>
        </div>
        <!-- End Banner Area -->

        <!-- Start Learn Area -->
        <section id="features" class="learn-section ptb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="learn-content">
                            <h3>The Features of New Beautiful Template</h3>
                            <div class="bar"></div>
                            <p>
                                {{ $general->app_name}} typically includes various features 
                                to provide a comprehensive and user-friendly platform for 
                                data plan purchases and recharges. Here are some of our
                                awesome features you can consider at {{ $general->app_name }}:
                            </p>

                            <ul class="learn-list">
                                <li>
                                    <i class='bx bx-chevrons-right'></i>
                                    24/7 Premium Customer Support.
                                </li>

                                <li>
                                    <i class='bx bx-chevrons-right'></i>
                                    Privacy and Data Security.
                                </li>

                                <li>
                                    <i class='bx bx-chevrons-right'></i>
                                    Flexible Plans and Services.
                                </li>

                                <li>
                                    <i class='bx bx-chevrons-right'></i>
                                    Mobile-Friendly Interface.
                                </li>
                            </ul>

                            <div class="learn-btn">
                                <a href="{{ env('APP_URL') . '/auth/register' }}" class="default-btn">Get Started</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="row">
                            <div class="col-lg-6 col-md-6">
                                <div class="learn-inner-content mb-30">
                                    <div class="icon">
                                        <i class="flaticon-blog"></i>
                                    </div>
                                    <h3>Flexible Plans and Services</h3>
                                    <p>Options for daily, weekly, and monthly data top-up plans for flexibility.</p>

                                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6">
                                <div class="learn-inner-content mb-30">
                                    <div class="icon bg-ba60fc">
                                        <i class="flaticon-blueprint"></i>
                                    </div>
                                    <h3>Privacy and Data Security</h3>
                                    <p>Transparent privacy policies ensuring confidentiality and user trust.</p>

                                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6">
                                <div class="learn-inner-content">
                                    <div class="icon bg-04cfc4">
                                        <i class="flaticon-clock"></i>
                                    </div>
                                    <h3>Customer Support</h3>
                                    <p>Live chat support and email assistance available for quick query resolution.</p>

                                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6">
                                <div class="learn-inner-content">
                                    <div class="icon bg-f9b854">
                                        <i class="flaticon-software"></i>
                                    </div>
                                    <h3>Mobile-Friendly Interface</h3>
                                    <p>Responsive design for seamless access and usability across various devices.</p>

                                    <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Learn Area -->

        <!-- Start App Area -->
        <section id="offer" class="app-section pb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="app-image">
                            <img src="img/20.png" alt="image">
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="app-content">
                            <h3>What We Offer</h3>
                            <div class="bar"></div>
                            <p>
                                Here at {{ $general->app_name }}, your go-to destination for seamless 
                                data recharges and virtual top-ups. We're dedicated to providing you 
                                with a range of services that empower you to stay connected, work 
                                efficiently, and enjoy your digital experiences without interruption.
                            </p>
                        </div>

                        <div class="app-inner-text">
                            <div class="icon">
                                <i class="flaticon-laptop"></i>
                            </div>
                            <h3>Secure Payment Options</h3>
                            <p>Multiple payment methods including credit/debit cards, mobile wallets, and online banking.</p>
                        </div>

                        <div class="app-inner-text">
                            <div class="icon">
                                <i class="flaticon-cloud-computing"></i>
                            </div>
                            <h3>Subscription Services</h3>
                            <p>Stay updated with the latest offerings, promotions, and tech tips via subscription.</p>
                        </div>

                        <div class="app-inner-text">
                            <div class="icon">
                                <i class="flaticon-cellphone"></i>
                            </div>
                            <h3>Instant Activation</h3>
                            <p>No more waiting around! Your data plan is activated instantly upon successful payment. You can start using your data right away, ensuring you're always connected when you need it most.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End App Area -->

        <!-- Start Data Area -->
        <section id="agent" class="data-section ptb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="data-content">
                            <h3>Become An Agent</h3>
                            <div class="bar"></div>
                            <p>
                                Join our network of outstanding entrepreneurs patnering 
                                with network.com bring the Network.com 'easy-payments' 
                                experience closer to your network and earn a commission 
                                for every transaction you perform for your customers.. 
                                We offer our Referrers the best referral program incentives 
                                to encourage entrepreneurial and managerial skill acquisition;
                                enhance growth and development and general empowerment among 
                                our students on campuses of higher learning and youths in diaspora. 
                                Finally, to promote technology via the use of ICT tools in our society..
                            </p>

                            
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="data-image">
                            <img src="img/img12.png" alt="image">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Data Area -->

        <!-- Start Services Area -->
        <section id="service" class="services-section pt-100 pb-70">
            <div class="container">
                <div class="section-title">
                    <h2>Our Awesome Services</h2>
                    <p>We're Providing Best Services To Our Customers</p>
                    <div class="bar"></div>
                </div>

                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="single-services">
                            <div class="icon">
                                <i class="flaticon-it"></i>
                            </div>
                            <h3>Bulk SMS</h3>
                            <p>
                                Get your sms in bulk at convenient rates, all you have to do is SIGN UP NOW....
                            </p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="single-services">
                            <div class="icon">
                                <i class="flaticon-setting"></i>
                            </div>
                            <h3>Exam Result Checker</h3>
                            <p>
                                Get your WAEC, NECO, GCE pin on {{ $general->app_name }} to check your exam result...
                            </p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="single-services">
                            <div class="icon">
                                <i class="flaticon-promotion"></i>
                            </div>
                            <h3>Airtime to Cash</h3>
                            <p>Convert your airtime easily to cash here at {{ $general->app_name }} with less charges...</p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="single-services">
                            <div class="icon">
                                <i class="flaticon-cellphone"></i>
                            </div>
                            <h3>Cable TV Subscription</h3>
                            <p>
                                Instantly activate cable subscription with favourable discount compare to others....
                            </p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="single-services">
                            <div class="icon">
                                <i class="flaticon-shopping-cart"></i>
                            </div>
                            <h3>Airtime to Cash</h3>
                            <p>Convert your airtime easily to cash here at {{ $general->app_name }} with less charges...</p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="single-services">
                            <div class="icon">
                                <i class="flaticon-optimize"></i>
                            </div>
                            <h3>Utility Bills Payments</h3>
                            <p>
                                We understand your needs, we have made bill and utilities payment more convenient....
                            </p>
                            <a href="{{ env('APP_URL') . '/auth/register' }}" class="read-btn">Get Started</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="default-shape">
                <div class="shape-1">
                    <img src="assets/img/shape/4.png" alt="image">
                </div>

                <div class="shape-2 rotateme">
                    <img src="assets/img/shape/5.svg" alt="image">
                </div>

                <div class="shape-3">
                    <img src="assets/img/shape/6.svg" alt="image">
                </div>

                <div class="shape-4">
                    <img src="assets/img/shape/7.png" alt="image">
                </div>

                <div class="shape-5">
                    <img src="assets/img/shape/8.png" alt="image">
                </div>
            </div>
        </section>
        <!-- End Services Area -->

        <!-- Start Customer Area -->
        <section id="app" class="customer-section ptb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="customer-content">
                            <h3>Download Our App</h3>
                            <div class="bar"></div>
                            <p>
                                Get ready to unlock a world of convenience, connectivity, 
                                and instant services right at your fingertips! {{ $general->app_name }}
                                 mobile app is your gateway to hassle-free data top-ups, utility bill 
                                 payments, exam result checking, and much more, anytime and anywhere.
                            </p>

                            <p>
                                Join thousands of users who have embraced the convenience of our mobile app. 
                                Whether you're a student, professional, or business owner, {{ $general->app_name }} 
                                is your gateway to effortless connectivity and essential services, right when you need them.
                            </p>

                            <div class="customer-btn">
                                <a href="#" class="default-btn">App Store</a>
                                <a href="#" class="optional-btn">Play Store</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="customer-image">
                            <img src="img/ade_phone.png" alt="image">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Customer Area -->


        
        <!-- Start Pricing Area -->
        <section id="pricing" class="pricing-section pt-100 pb-70">
            <div class="container">
                <div class="section-title">
                    <h2>Our <span>Pricing</span> Plan</h2>
                    <div class="bar"></div>
                    <p>
                        We understand that every individual's data needs are unique. 
                        That's why we offer a diverse range of pricing plans tailored 
                        to suit various usage patterns and preferences. Whether you're a 
                        light user, a heavy data consumer, or somewhere in between, we have 
                        the perfect plan for you.
                    </p>
            
                </div>

       
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="single-pricing">
                        <div class="pricing-header">

                            <img src="img/mtn.png" style="height: 100px; ">
                            <span class="excerpt d-block mt-3">MTN DATA</span>
                            <div class="pricing-text mb-3">
                                <table class="table table-all ">
                                    @foreach ($mtn as $mtns)
                                        <tr>
                                            <td
                                                style="color: rgb(5, 37, 78); font-size:16px; font-weight: bolder;">
                                                {{ $mtns->plan_name }}{{ $mtns->plan_size }}
                                            </td>
                                            <td
                                                style="color: rgb(5, 37, 78); font-size:16px;  font-weight: bolder;">
                                                <i> &#8358;{{ $mtns->smart }}</i>
                                            </td>
                                            <td
                                                style="color: rgb(5, 37, 78); font-size:12px;  font-weight: bolder;">
                                                <i>{{ $mtns->plan_day }}</i>
                                            </td>
                                        </tr>
                                    @endforeach
  
                                </table>
                            </div>
  
                            <!-- <a href="/profile" class="btn text-white d-block px-2 py-3" style=" background-image: linear-gradient(to bottom right ,#dc3545, #ab125f, #6d18a9)"><i class="fa fa-shopping-cart"></i> Order now</a> -->
                            <div class="price-btn">
                                <a href="pricing.html" class="default-btn">
                                    Buy Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
  
               <div class="col-lg-4 col-md-6">
                        <div class="single-pricing">
                            <div class="pricing-header">

                            <img src="img/airtel.png" height="100" style="height: 100px;">
                            <span class="excerpt d-block mt-3">AIRTEL DATA</span>
                            <div class="pricing-text mb-3">
                                <table class="table table-all ">
                                    <table class="table table-all ">
                                        @foreach ($airtel as $mtns)
                                            <tr>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:16px; font-weight: bolder;">
                                                    {{ $mtns->plan_name }}{{ $mtns->plan_size }}
                                                </td>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:16px;  font-weight: bolder;">
                                                    <i> &#8358;{{ $mtns->smart }}</i>
                                                </td>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:12px;  font-weight: bolder;">
                                                    <i>{{ $mtns->plan_day }}</i>
                                                </td>
                                            </tr>
                                        @endforeach
  
                                    </table>
                                </table>
                            </div>
  
  
                            <div class="price-btn">
                                <a href="pricing.html" class="default-btn">
                                    Buy Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
  
                <div class="col-lg-4 col-md-6">
                        <div class="single-pricing">
                            <div class="pricing-header">

                            <img src="img/glo.png" height="100" style="height: 100px;">
                            <span class="excerpt d-block mt-3">GLO DATA</span>
                            <div class="pricing-text mb-3">
                                <table class="table table-all ">
                                    <table class="table table-all ">
                                        @foreach ($glo as $mtns)
                                            <tr>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:16px; font-weight: bolder;">
                                                    {{ $mtns->plan_name }}{{ $mtns->plan_size }}
                                                </td>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:16px;  font-weight: bolder;">
                                                    <i> &#8358;{{ $mtns->smart }}</i>
                                                </td>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:12px;  font-weight: bolder;">
                                                    <i>{{ $mtns->plan_day }}</i>
                                                </td>
                                            </tr>
                                        @endforeach
  
                                    </table>
                                </table>
                            </div>
  
                            <div class="price-btn">
                                <a href="pricing.html" class="default-btn">
                                    Buy Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
  
               <div class="col-lg-4 col-md-6">
                        <div class="single-pricing">
                            <div class="pricing-header">

                            <img src="img/9mobile.png" style="height: 100px">
                            <span class="excerpt d-block mt-3">9MOBILE DATA</span>
                            <div class="pricing-text mb-3">
                                <table class="table table-all ">
                                    <table class="table table-all ">
                                        @foreach ($mobile as $mtns)
                                            <tr>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:16px; font-weight: bolder;">
                                                    {{ $mtns->plan_name }}{{ $mtns->plan_size }}
                                                </td>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:16px;  font-weight: bolder;">
                                                    <i> &#8358;{{ $mtns->smart }}</i>
                                                </td>
                                                <td
                                                    style="color: rgb(5, 37, 78); font-size:12px;  font-weight: bolder;">
                                                    <i>{{ $mtns->plan_day }}</i>
                                                </td>
                                            </tr>
                                        @endforeach
  
                                    </table>
                                </table>
                            </div>
  
                            <div class="price-btn">
                                <a href="pricing.html" class="default-btn">
                                    Buy Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
       
            <div class="default-shape">
                <div class="shape-1">
                    <img src="assets/img/shape/4.png" alt="image">
                </div>

                <div class="shape-2 rotateme">
                    <img src="assets/img/shape/5.svg" alt="image">
                </div>

                <div class="shape-3">
                    <img src="assets/img/shape/6.svg" alt="image">
                </div>

                <div class="shape-4">
                    <img src="assets/img/shape/7.png" alt="image">
                </div>

                <div class="shape-5">
                    <img src="assets/img/shape/8.png" alt="image">
                </div>
            </div>
        </section>
        <!-- End Pricing Area -->


        <!-- Start Clients Area -->
        <section id="testimonial" class="clients-section bg-background ptb-100">
            <div class="container">
                <div class="section-title">
                    <h2>What Our <span>Clients</span> Says</h2>
                    <div class="bar"></div>
                    <p>
                        Here at {{ $general->app_name }}, our commitment to providing exceptional service 
                        is reflected in the experiences of our valued customers. Don't just take our word 
                        for it – here's what they have to say about their experiences using our platform:
                    </p>

                </div>

                <div class="clients-slider owl-carousel owl-theme">
                    <div class="clients-item">
                        <div class="icon">
                            <i class="flaticon-left-quotes-sign"></i>
                        </div>

                        <p>
                            "I've been using {{ $general->app_name }} for my data top-ups, and i must 
                            say, it's incredibly convenient. The range of plans suits my needs perfectly, 
                            and the instant activation ensures I'm always connected. Highly recommended!"
                        </p>

                        <div class="clients-content">
                            <h3>Godly</h3>
                            <span>User</span>
                        </div>

                    </div>

                    <div class="clients-item">
                        <div class="icon">
                            <i class="flaticon-left-quotes-sign"></i>
                        </div>

                        <p>
                            "I've been using the Bulk SMS service from {{ $general->app_name }} 
                            for my business promotions. The high open rates and instant reach have 
                            significantly boosted my marketing campaigns. A fantastic tool!"
                        </p>

                        <div class="clients-content">
                            <h3>Billy Ojay</h3>
                            <span>User</span>
                        </div>

                    </div>

                    <div class="clients-item">
                        <div class="icon">
                            <i class="flaticon-left-quotes-sign"></i>
                        </div>

                        <p>
                            "I've been using the Bulk SMS service from {{ $general->app_name }} 
                            for my business promotions. The high open rates and instant reach have 
                            significantly boosted my marketing campaigns. A fantastic tool!"
                        </p>

                        <div class="clients-content">
                            <h3>Billy Ojay</h3>
                            <span>User</span>
                        </div>

                    </div>
                </div>
            </div>
        </section> <br> <br> <br> <br>
        <!-- End Clients Area -->


         <!-- Start Faq Area -->
        <section id="faq" class="faq-section pb-100">
            <div class="container">
                <div class="section-title">
                    <h2>Frequently <span>Asked</span> Questions</h2>
                    <div class="bar"></div>
                    <p>
                        If you have any additional questions or concerns 
                        that are not addressed here, please don't hesitate 
                        to reach out to our customer support team. We're here 
                        to provide you with the assistance you need and ensure 
                        that your experience with our VTU data services is smooth 
                        and satisfactory.
                    </p>

                </div>


                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="faq-accordion">
                            <ul class="accordion">
                                <li class="accordion-item">
                                    <a class="accordion-title active" href="javascript:void(0)">
                                        <i class='bx bx-chevron-down'></i>
                                        What payment methods are accepted on the platform?
                                    </a>
                            
                                    <div class="accordion-content show">
                                        <p>We accept various payment methods, including credit/debit cards, mobile wallets, and online banking, providing secure and convenient options for our users..</p>
                                    </div>
                                </li>

                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class='bx bx-chevron-down'></i>
                                        Can I check exam results on this platform?
                                    </a>
                            
                                    <div class="accordion-content">
                                        <p>Yes, our platform offers an Exam Result Checker service. Select your educational institution or examination board, enter the required details, and instantly check your exam results hassle-free..</p>
                                    </div>
                                </li>

                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class='bx bx-chevron-down'></i>
                                        How secure is the platform for making transactions?
                                    </a>
                            
                                    <div class="accordion-content">
                                        <p>We prioritize user security. Our platform employs robust security measures to ensure that all transactions and user data are handled with the utmost confidentiality and encryption protocols..</p>
                                    </div>
                                </li>

                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class='bx bx-chevron-down'></i>
                                        Can I reach customer support if I encounter issues or have queries?
                                    </a>
                            
                                    <div class="accordion-content">
                                        <p>Absolutely! Our dedicated customer support team is available via live chat and email to assist you with any issues or queries you may have. We are committed to providing prompt and helpful assistance.</p>
                                    </div>
                                </li>

                                <li class="accordion-item">
                                    <a class="accordion-title" href="javascript:void(0)">
                                        <i class='bx bx-chevron-down'></i>
                                        How can I subscribe to receive updates and offers from the platform?
                                    </a>
                            
                                    <div class="accordion-content">
                                        <p>To subscribe and receive updates, promotions, and exclusive offers, simply enter your email address in the subscription box provided on our website's homepage and confirm your subscription..</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="faq-image">
                            <img src="img/img18.png" alt="image">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Faq Area -->

         <!-- Start Contact Box Area -->
		<section id="contact" class="contact-box pt-100 pb-70">

            <div class="section-title">
                <h2>Contact <span>Us</span></h2>

                <div class="bar"></div>
            </div>

			<div class="container">
				<div class="row">
					<div class="col-lg-4 col-md-6">
						<div class="single-contact-box">
							<i class="flaticon-pin"></i>
							<div class="content-title">
								<h3>Address</h3>
								<p>{{ $general->app_address }}</p>
							</div>
						</div>
                    </div>
                    
					<div class="col-lg-4 col-md-6">
						<div class="single-contact-box">
							<i class="flaticon-envelope"></i>
							<div class="content-title">
								<h3>Email</h3>
								<a href="{{ $general->app_email }}">{{ $general->app_email }}</a>
							</div>
						</div>
                    </div>
                    
					<div class="col-lg-4 col-md-6 offset-md-3 offset-lg-0">
						<div class="single-contact-box">
							<i class="flaticon-phone-call"></i>
							<div class="content-title">
								<h3>Phone</h3>
								<a href="{{ $general->app_phone }}">{{ $general->app_phone }}</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- End Contact Box Area -->

        <!-- Start Contact Area -->
        <section class="contact-section pb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="contact-text">
                            <h3>Have Any Questions About Us?</h3>
                            <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed eiusmod tempor incididunt ut labore </p>
                        </div>

                        <div class="contact-form">
                            <form id="contactForm">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="name" id="name" class="form-control" required="" data-error="Please enter your name" placeholder="Name">
                                    <div class="help-block with-errors"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" id="email" class="form-control" required="" data-error="Please enter your email" placeholder="Your Email">
                                    <div class="help-block with-errors"></div>
                                </div>
                               
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea name="message" class="form-control" id="message" cols="30" rows="6" required="" data-error="Write your message" placeholder="Your Message"></textarea>
                                    <div class="help-block with-errors"></div>
                                </div>

                                <div class="send-btn">
                                    <button type="submit" class="default-btn">
                                        Send Message
                                    </button>
                                    <div id="msgSubmit" class="h3 text-center hidden"></div>
                                    <div class="clearfix"></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="contact-image">
                            <img src="img/24.png" alt="image">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Contact Area -->
       
        <!-- Start Footer Area -->
        <section class="footer-section pt-100 pb-70">
            <div class="container">
                <div class="subscribe-area">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-md-6">
                            <div class="subscribe-content">
                                <h2>Join Our Newsletter</h2>
                                <p>
                                    Experience the best data & airtime topup for all networks, cable subscription, electricity 
                                    bills payment, recharge card printing, bulk SMS and many more on {{ $general->app_name }}..
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <form class="newsletter-form">
                                <input type="email" class="input-newsletter" placeholder="adexplug@gmail" name="EMAIL" required="" autocomplete="off">
                                <button type="submit">
                                    Subscribe Now
                                </button>
								
                                <div id="validator-newsletter" class="form-result"></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-footer-widget">
                            <div class="footer-heading">
                                <h3>About Us</h3>
                            </div>
                            <p>
                                Experience the best data & airtime topup for all networks, cable subscription, electricity 
                                bills payment, recharge card printing, bulk SMS and many more on {{ $general->app_name }}..
                            </p>

                            <ul class="footer-social">
                                <li>
                                    <a href="#">
                                        <i class="flaticon-facebook"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="#">
                                        <i class="flaticon-twitter"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="#">
                                        <i class="flaticon-pinterest"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="#">
                                        <i class="flaticon-instagram"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-footer-widget">
                            <div class="footer-heading">
                                <h3>Important Links</h3>
                            </div>

                            <ul class="footer-quick-links">
                                <li>
                                    <a href="#about">About Us</a>
                                </li>
                                <li>
                                    <a href="#testimonial">Client Reviews</a>
                                </li>
                                <li>
                                    <a href="#service">Services</a>
                                </li>
                                <li>
                                    <a href="#pricing">Pricing Plan</a>
                                </li>
                                <li>
                                    <a href="#contact">Contact</a>
                                </li>
                            </ul>
                        </div>    
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-footer-widget">
                            <div class="footer-heading">
                                <h3>Featured Service</h3>
                            </div>
                            <ul class="footer-quick-links">
                                <li>
                                    <a href="{{ env('APP_URL') . '/auth/register' }}">Airtime Topup</a>
                                </li>
                                <li>
                                    <a href="{{ env('APP_URL') . '/auth/register' }}">Cable TV Subscription</a>
                                </li>
                                <li>
                                    <a href="{{ env('APP_URL') . '/auth/register' }}">Utility Bilss Payments</a>
                                </li>
                                <li>
                                    <a href="{{ env('APP_URL') . '/auth/register' }}">Exam Result Checker</a>
                                </li>
                                <li>
                                    <a href="{{ env('APP_URL') . '/auth/register' }}">Bulk SMS</a>
                                </li>
                            </ul>
                        </div>    
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="single-footer-widget">
                            <div class="footer-heading">
                                <h3>Contact</h3>
                            </div>

                            <div class="footer-info-contact">
                                <i class="flaticon-phone-call"></i>
                                <h3>Phone</h3>
                                <span><a href="{{ $general->app_phone }}">{{ $general->app_phone }}</a></span>
                            </div>

                            <div class="footer-info-contact">
                                <i class="flaticon-envelope"></i>
                                <h3>Email</h3>
                                <span><a href="{{ $general->app_email }}">{{ $general->app_email }}</a></span>
                            </div>

                            <div class="footer-info-contact">
                                <i class="flaticon-pin"></i>
                                <h3>Address</h3>
                                <span>{{ $general->app_address }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Footer Area -->

        <!-- Start Copy Right Area -->
        <div class="copyright-area">
            <div class="container">
                <div class="copyright-area-content">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-md-6">
                            <p>
                                Copyright © <strong>{{ $general->app_name }}</strong> {{date('Y')}}. Designed By <a href="https://adehosting.com" rel="noopener">A D E Developers</a>
                              </p>
                        </div>

                        <div class="col-lg-6 col-md-6">
                            <ul>
                                <li>
                                    <a href="/">Terms & Conditions</a>
                                </li>
                                <li>
                                    <a href="/">Privacy Policy</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Copy Right Area -->

        <!-- Start Go Top Section -->
        <div class="go-top">
            <i class="bx bx-chevron-up"></i>
            <i class="bx bx-chevron-up"></i>
        </div>
        <!-- End Go Top Section -->
     
        <!-- dark version -->
        <div class="dark-version">
            <label id="switch" class="switch">
                <input type="checkbox" onchange="toggleTheme()" id="slider">
                <span class="slider round"></span>
            </label>
        </div>
        <!-- dark version -->

        <!-- Jquery Slim JS -->
        <script src="assets/js/jquery.min.js"></script>
        <!-- Popper JS -->
        <script src="assets/js/popper.min.js"></script>
        <!-- Bootstrap JS -->
        <script src="assets/js/bootstrap.min.js"></script>
        <!-- Meanmenu JS -->
        <script src="assets/js/jquery.meanmenu.js"></script>
        <!-- Owl Carousel JS -->
		<script src="assets/js/owl.carousel.js"></script>
        <!-- Magnific JS -->
		<script src="assets/js/jquery.magnific-popup.min.js"></script>
		<!-- Appear JS --> 
        <script src="assets/js/jquery.appear.min.js"></script>
		<!-- Odometer JS --> 
		<script src="assets/js/odometer.min.js"></script>
		<!-- Form Ajaxchimp JS -->
		<script src="assets/js/jquery.ajaxchimp.min.js"></script>
		<!-- Form Validator JS -->
		<script src="assets/js/form-validator.min.js"></script>
		<!-- Contact JS -->
        <script src="assets/js/contact-form-script.js"></script>
        <!-- Wow JS -->
        <script src="assets/js/wow.min.js"></script>
        <!-- Custom JS -->
        <script src="assets/js/main.js"></script>
    </body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMS | Farmer Assistance Management System</title>
    <meta name="description" content="FAMS is a web-based decision support system helping the Municipal Agriculture Office manage farmer profiles, weather monitoring, government assistance programs, and analytics in one platform.">

    {{-- Google Font: Poppins --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    {{-- FAMS custom stylesheet --}}
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
</head>
<body>

{{-- =========================================================
     Navigation Bar
     ========================================================= --}}
<nav class="navbar navbar-expand-lg fams-navbar" id="famsNavbar">
    <div class="container">
        <a class="navbar-brand fams-brand" href="{{ url('/') }}">
            <span class="fams-brand-mark"><i class="bi bi-flower2"></i></span>
            <span class="fams-brand-text">
                <span class="brand-main">FAMS</span>
                <span class="brand-sub">Farmer Assistance Management System</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#famsNavContent" aria-controls="famsNavContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="famsNavContent">
            <ul class="navbar-nav mx-auto my-3 my-lg-0">
                <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#weather">Weather</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>

            <div class="d-flex gap-2 flex-column flex-lg-row">
                <a href="{{ Route::has('login') ? route('login') : '#' }}" class="btn btn-nav-login">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-nav-register">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </a>
                @endif
            </div>
        </div>
    </div>
</nav>

{{-- =========================================================
     Hero Section
     ========================================================= --}}
<header class="fams-hero" id="home">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <div class="hero-eyebrow reveal is-visible">
                    <span class="pulse-dot"><i class="bi bi-broadcast"></i></span>
                    Municipal Agriculture Office &middot; Bulan, Sorsogon
                </div>

                <h1 class="hero-title reveal is-visible">
                    Farmer Assistance <span class="text-accent-underline">Management System</span>
                </h1>

                <p class="hero-subtitle reveal is-visible reveal-delay-1">
                    Empowering Farmers Through Smart Technology and Data-Driven Decision Support.
                </p>

                <p class="hero-description reveal is-visible reveal-delay-2">
                    FAMS helps farmers and the Municipal Agriculture Office manage farmer profiles, weather monitoring,
                    government assistance programs, recommendations, announcements, distribution monitoring, and
                    analytics &mdash; all through one intelligent platform.
                </p>

                <div class="hero-cta reveal is-visible reveal-delay-3">
                    <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn btn-fams-primary">
                        Get Started <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="#about" class="btn btn-fams-outline">
                        Learn More
                    </a>
                </div>

                <div class="hero-trust reveal is-visible reveal-delay-4">
                    <div class="hero-trust-item">
                        <i class="bi bi-shield-check"></i>
                        <span>Verified Farmer Records</span>
                    </div>
                    <div class="hero-trust-item">
                        <i class="bi bi-cloud-sun"></i>
                        <span>Live Weather Monitoring</span>
                    </div>
                    <div class="hero-trust-item">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Data-Driven Insights</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-visual reveal is-visible reveal-delay-2">
                    <div class="hero-visual-stage">
                        {{-- Rice field + technology illustration --}}
                        <svg viewBox="0 0 600 520" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Illustration of rice fields with agricultural technology">
                            <defs>
                                <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.18"/>
                                    <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
                                </linearGradient>
                                <linearGradient id="fieldGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#FFC107" stop-opacity="0.85"/>
                                    <stop offset="100%" stop-color="#FFA000" stop-opacity="0.9"/>
                                </linearGradient>
                            </defs>

                            <rect width="600" height="520" fill="url(#skyGrad)"/>

                            {{-- Sun --}}
                            <circle cx="470" cy="110" r="46" fill="#FFC107" opacity="0.9"/>
                            <g opacity="0.5">
                                <circle cx="470" cy="110" r="66" fill="none" stroke="#FFFFFF" stroke-width="2" stroke-dasharray="4 8"/>
                            </g>

                            {{-- Clouds --}}
                            <g fill="#FFFFFF" opacity="0.55">
                                <ellipse cx="120" cy="90" rx="46" ry="20"/>
                                <ellipse cx="155" cy="80" rx="34" ry="18"/>
                                <ellipse cx="90" cy="80" rx="28" ry="16"/>
                            </g>

                            {{-- Rolling terraces --}}
                            <path d="M0 330 Q150 300 300 330 T600 330 V520 H0 Z" fill="#FFFFFF" opacity="0.14"/>
                            <path d="M0 380 Q150 350 300 380 T600 380 V520 H0 Z" fill="url(#fieldGrad)" opacity="0.85"/>
                            <path d="M0 430 Q150 400 300 430 T600 430 V520 H0 Z" fill="#1B5E20" opacity="0.55"/>

                            {{-- Rice rows --}}
                            <g stroke="#1B5E20" stroke-opacity="0.35" stroke-width="2">
                                <path d="M20 460 Q300 440 580 460"/>
                                <path d="M20 480 Q300 462 580 480"/>
                                <path d="M20 500 Q300 484 580 500"/>
                            </g>

                            {{-- Simple farmer figure --}}
                            <g transform="translate(230,330)">
                                <circle cx="0" cy="0" r="14" fill="#FFFFFF"/>
                                <rect x="-10" y="12" width="20" height="46" rx="8" fill="#FFFFFF" opacity="0.92"/>
                                <rect x="-16" y="58" width="12" height="34" rx="5" fill="#1B5E20" opacity="0.7"/>
                                <rect x="4" y="58" width="12" height="34" rx="5" fill="#1B5E20" opacity="0.7"/>
                                <path d="M-24 -6 L0 -22 L24 -6 Z" fill="#FFC107"/>
                            </g>

                            {{-- Drone / tech marker --}}
                            <g transform="translate(340,180)" opacity="0.95">
                                <circle r="20" fill="#FFFFFF"/>
                                <circle r="20" fill="none" stroke="#FFFFFF" stroke-width="2" opacity="0.6">
                                    <animate attributeName="r" values="20;30;20" dur="3s" repeatCount="indefinite"/>
                                    <animate attributeName="opacity" values="0.6;0;0.6" dur="3s" repeatCount="indefinite"/>
                                </circle>
                                <path d="M-6 -2 L6 -2 L6 6 L-6 6 Z" fill="#2E7D32"/>
                                <line x1="-14" y1="-10" x2="-6" y2="-2" stroke="#2E7D32" stroke-width="2"/>
                                <line x1="14" y1="-10" x2="6" y2="-2" stroke="#2E7D32" stroke-width="2"/>
                                <line x1="-14" y1="14" x2="-6" y2="6" stroke="#2E7D32" stroke-width="2"/>
                                <line x1="14" y1="14" x2="6" y2="6" stroke="#2E7D32" stroke-width="2"/>
                            </g>
                        </svg>
                    </div>

                    {{-- Floating card: weather --}}
                    <div class="float-card float-card-weather">
                        <div class="fc-top">
                            <span class="fc-icon" style="background:var(--fams-gradient-accent);"><i class="bi bi-cloud-sun-fill"></i></span>
                            <div>
                                <div class="fc-title">Bulan, Sorsogon</div>
                                <div class="fc-sub">Partly Cloudy</div>
                            </div>
                        </div>
                        <div class="fc-value">29&deg;C</div>
                    </div>

                    {{-- Floating card: mobile mockup --}}
                    <div class="float-card float-card-mobile">
                        <div class="fc-top">
                            <span class="fc-icon" style="background:var(--fams-gradient);"><i class="bi bi-phone"></i></span>
                            <div>
                                <div class="fc-title">FAMS Mobile</div>
                                <div class="fc-sub">Farmer Portal</div>
                            </div>
                        </div>
                        <div class="fc-sub">Track programs & alerts on the go</div>
                    </div>

                    {{-- Floating card: stat --}}
                    <div class="float-card float-card-stat">
                        <div class="fc-top">
                            <span class="fc-icon" style="background:var(--fams-gradient);"><i class="bi bi-people-fill"></i></span>
                            <div class="fc-title">Registered</div>
                        </div>
                        <div class="fc-value">3,240+</div>
                        <div class="fc-sub">Active Farmers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- =========================================================
     About Section
     ========================================================= --}}
<section id="about">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center section-header reveal">
                <span class="section-eyebrow"><span class="divider-dot"></span> About the System</span>
                <h2 class="section-title">About FAMS</h2>
                <p class="section-subtitle mx-auto">
                    FAMS is a web-based decision support system designed to help the Municipal Agriculture Office manage
                    farmer information, provide weather monitoring, recommend assistance programs, and improve
                    agricultural services using data-driven technologies.
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="about-card reveal reveal-delay-1">
                    <div class="about-icon"><i class="bi bi-bullseye"></i></div>
                    <h4>Mission</h4>
                    <p>To equip the Municipal Agriculture Office with reliable, data-driven tools that streamline
                        farmer services, from registration to the delivery of government assistance.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="about-card reveal reveal-delay-2">
                    <div class="about-icon"><i class="bi bi-eye"></i></div>
                    <h4>Vision</h4>
                    <p>A digitally empowered farming community where every farmer has access to timely weather
                        information, fair program distribution, and personalized recommendations.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="about-card reveal reveal-delay-3">
                    <div class="about-icon"><i class="bi bi-list-check"></i></div>
                    <h4>Objectives</h4>
                    <p>Centralize farmer records, automate weather-based advisories, and provide analytics that
                        support faster, evidence-based agricultural planning.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================
     System Features
     ========================================================= --}}
<section id="features" class="bg-alt">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mx-auto text-center section-header reveal">
                <span class="section-eyebrow"><span class="divider-dot"></span> Core Modules</span>
                <h2 class="section-title">System Features</h2>
                <p class="section-subtitle mx-auto">Everything the Municipal Agriculture Office needs to serve
                    farmers efficiently, in one connected platform.</p>
            </div>
        </div>

        <div class="row g-4">
            @php
                $features = [
                    ['icon' => 'bi-person-vcard', 'title' => 'Farmer Registration', 'desc' => 'Digitally register and maintain verified farmer profiles, farm locations, and crop details in one central database.'],
                    ['icon' => 'bi-cloud-sun', 'title' => 'Weather Monitoring', 'desc' => 'Track real-time local weather conditions and receive predictive advisories to guide planting and harvest decisions.'],
                    ['icon' => 'bi-clipboard2-check', 'title' => 'Program Management', 'desc' => 'Create, manage, and monitor government assistance programs from application through approval.'],
                    ['icon' => 'bi-lightbulb', 'title' => 'Recommendation System', 'desc' => 'Get data-backed suggestions on suitable crops, farming practices, and eligible assistance programs.'],
                    ['icon' => 'bi-truck', 'title' => 'Distribution Monitoring', 'desc' => 'Track the distribution of seeds, fertilizers, and equipment to ensure accountable, transparent delivery.'],
                    ['icon' => 'bi-bar-chart-line', 'title' => 'Analytics Dashboard', 'desc' => 'Visualize farmer demographics, program impact, and weather trends through interactive reports.'],
                ];
            @endphp

            @foreach ($features as $i => $feature)
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card reveal reveal-delay-{{ ($i % 3) + 1 }}">
                        <span class="feature-index">{{ sprintf('%02d', $i + 1) }}</span>
                        <div class="feature-icon"><i class="bi {{ $feature['icon'] }}"></i></div>
                        <h4>{{ $feature['title'] }}</h4>
                        <p>{{ $feature['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- =========================================================
     How the System Works — Timeline
     ========================================================= --}}
<section id="how-it-works">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mx-auto text-center section-header reveal">
                <span class="section-eyebrow"><span class="divider-dot"></span> Process</span>
                <h2 class="section-title">How the System Works</h2>
                <p class="section-subtitle mx-auto">From registration to reporting &mdash; six connected steps
                    keep every farmer record moving forward.</p>
            </div>
        </div>

        <div class="timeline-wrap">
            <div class="timeline-track position-relative">
                <div class="timeline-connector d-none d-md-block"></div>
                <div class="row gy-4">
                    @php
                        $steps = [
                            ['icon' => 'bi-person-plus', 'title' => 'Farmer Registration', 'desc' => 'Farmers submit profile and farm details.'],
                            ['icon' => 'bi-patch-check', 'title' => 'Farmer Verification', 'desc' => 'Office staff validate submitted records.'],
                            ['icon' => 'bi-cloud-sun', 'title' => 'Weather Monitoring', 'desc' => 'System tracks live conditions per area.'],
                            ['icon' => 'bi-stars', 'title' => 'Program Recommendation', 'desc' => 'Farmers matched to eligible programs.'],
                            ['icon' => 'bi-box-seam', 'title' => 'Distribution Monitoring', 'desc' => 'Resources tracked from release to receipt.'],
                            ['icon' => 'bi-graph-up', 'title' => 'Reports & Analytics', 'desc' => 'Insights generated for planning ahead.'],
                        ];
                    @endphp

                    @foreach ($steps as $i => $step)
                        <div class="col-6 col-md-2">
                            <div class="timeline-step reveal reveal-delay-{{ $i + 1 }}">
                                <div class="timeline-node">
                                    <span class="timeline-step-num">{{ $i + 1 }}</span>
                                    <i class="bi {{ $step['icon'] }}"></i>
                                </div>
                                <h6>{{ $step['title'] }}</h6>
                                <p>{{ $step['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================
     Live Weather Preview
     ========================================================= --}}
<section id="weather" class="bg-alt">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mx-auto text-center section-header reveal">
                <span class="section-eyebrow"><span class="divider-dot"></span> Weather Monitoring</span>
                <h2 class="section-title">Live Weather Preview</h2>
                <p class="section-subtitle mx-auto">Current conditions for the municipality, updated continuously
                    to support timely farming decisions.</p>
            </div>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="weather-panel h-100 reveal reveal-delay-1">
                    <div class="weather-loc"><i class="bi bi-geo-alt-fill"></i> Bulan, Sorsogon</div>
                    <div class="weather-alert"><span class="alert-dot"></span> Alert Level: Normal</div>

                    <div class="weather-main">
                        <div class="weather-icon-big"><i class="bi bi-cloud-sun-fill"></i></div>
                        <div>
                            <div class="weather-temp">29&deg;C</div>
                            <div class="weather-desc">Partly Cloudy &middot; Feels like 32&deg;C</div>
                        </div>
                    </div>

                    <div class="weather-metric-grid">
                        <div class="weather-metric">
                            <i class="bi bi-droplet-half"></i>
                            <div class="wm-value">78%</div>
                            <div class="wm-label">Humidity</div>
                        </div>
                        <div class="weather-metric">
                            <i class="bi bi-wind"></i>
                            <div class="wm-value">14 km/h</div>
                            <div class="wm-label">Wind Speed</div>
                        </div>
                        <div class="weather-metric">
                            <i class="bi bi-speedometer2"></i>
                            <div class="wm-value">1011 hPa</div>
                            <div class="wm-label">Pressure</div>
                        </div>
                        <div class="weather-metric">
                            <i class="bi bi-cloud-rain"></i>
                            <div class="wm-value">4.2 mm</div>
                            <div class="wm-label">Rainfall</div>
                        </div>
                        <div class="weather-metric">
                            <i class="bi bi-clouds"></i>
                            <div class="wm-value">62%</div>
                            <div class="wm-label">Cloud Cover</div>
                        </div>
                        <div class="weather-metric">
                            <i class="bi bi-shield-check"></i>
                            <div class="wm-value">Normal</div>
                            <div class="wm-label">Alert Level</div>
                        </div>
                    </div>

                    <a href="#" class="btn btn-fams-accent">
                        <i class="bi bi-graph-up me-1"></i> View Weather Dashboard
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="weather-advisory-card reveal reveal-delay-2">
                    <h5><i class="bi bi-lightbulb-fill"></i> Predictive Advisory</h5>
                    <p class="mb-3">Based on current conditions and short-term forecasts, here is what farmers in
                        the area should keep in mind this week:</p>
                    <ul class="advisory-list">
                        <li><i class="bi bi-check-circle"></i> Favorable conditions for transplanting rice seedlings through Thursday.</li>
                        <li><i class="bi bi-check-circle"></i> Moderate rainfall expected &mdash; delay fertilizer application until skies clear.</li>
                        <li><i class="bi bi-check-circle"></i> Wind speeds remain within safe range for spraying operations.</li>
                        <li><i class="bi bi-check-circle"></i> Monitor humidity levels closely to prevent fungal crop disease.</li>
                    </ul>
                    <a href="#contact" class="btn btn-fams-outline w-100">
                        <i class="bi bi-bell me-1"></i> Get Advisory Alerts
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================
     Dashboard Statistics
     ========================================================= --}}
<section class="stats-section">
    <div class="container">
        <div class="row g-4">

            @foreach ($stats as $i => $stat)

                <div class="col-6 col-lg-3">

                    <div class="stat-card reveal reveal-delay-{{ $i + 1 }}">

                        <div class="stat-icon">
                            <i class="bi {{ $stat['icon'] }}"></i>
                        </div>

                        <div
                            class="stat-number"
                            data-counter="{{ $stat['value'] }}"
                            data-suffix="{{ $stat['suffix'] }}"
                        >
                            0
                        </div>

                        <div class="stat-label">
                            {{ $stat['label'] }}
                        </div>

                    </div>

                </div>

            @endforeach

        </div>
    </div>
</section>

{{-- =========================================================
     Why Choose FAMS
     ========================================================= --}}
<section id="why-fams" class="bg-alt">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mx-auto text-center section-header reveal">
                <span class="section-eyebrow"><span class="divider-dot"></span> Advantages</span>
                <h2 class="section-title">Why Choose FAMS</h2>
                <p class="section-subtitle mx-auto">Built specifically for local agriculture offices and the
                    farmers they serve.</p>
            </div>
        </div>

        <div class="row g-3">
            @php
                $reasons = [
                    ['icon' => 'bi-ui-checks', 'title' => 'Easy Farmer Registration', 'desc' => 'A simple, guided process for onboarding new farmers and their farm data.'],
                    ['icon' => 'bi-cloud-sun', 'title' => 'Real-Time Weather Monitoring', 'desc' => 'Continuously updated conditions specific to the municipality.'],
                    ['icon' => 'bi-graph-up-arrow', 'title' => 'Predictive Recommendations', 'desc' => 'Suggestions grounded in weather trends and historical data.'],
                    ['icon' => 'bi-clipboard-data', 'title' => 'Government Program Management', 'desc' => 'Centralized tracking of every assistance program in one place.'],
                    ['icon' => 'bi-chat-dots', 'title' => 'Automatic SMS Notifications', 'desc' => 'Farmers stay informed of alerts and program updates instantly.'],
                    ['icon' => 'bi-pie-chart', 'title' => 'Analytics Dashboard', 'desc' => 'Clear, visual reporting to guide agricultural planning decisions.'],
                ];
            @endphp

            @foreach ($reasons as $i => $reason)
                <div class="col-md-6 col-lg-4">
                    <div class="reason-item reveal reveal-delay-{{ ($i % 3) + 1 }}">
                        <div class="reason-icon-circle"><i class="bi {{ $reason['icon'] }}"></i></div>
                        <div>
                            <h6>{{ $reason['title'] }}</h6>
                            <p>{{ $reason['desc'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- =========================================================
     Contact Section
     ========================================================= --}}
<section id="contact">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 mx-auto text-center section-header reveal">
                <span class="section-eyebrow"><span class="divider-dot"></span> Get in Touch</span>
                <h2 class="section-title">Contact the Office</h2>
                <p class="section-subtitle mx-auto">Have questions about registration or available programs?
                    Reach out to the Municipal Agriculture Office.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="contact-info-card reveal reveal-delay-1">
                    <h5 class="mb-1 fw-800">Municipal Agriculture Office</h5>
                    <p class="mb-4" style="font-size:0.9rem;">Bulan, Sorsogon, Philippines</p>

                    <div class="contact-info-row">
                        <div class="contact-info-icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="ci-label">Address</div>
                            <div class="ci-value">Municipal Hall, Bulan, Sorsogon</div>
                        </div>
                    </div>
                    <div class="contact-info-row">
                        <div class="contact-info-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="ci-label">Email</div>
                            <div class="ci-value">agriculture.bulan@sorsogon.gov.ph</div>
                        </div>
                    </div>
                    <div class="contact-info-row">
                        <div class="contact-info-icon"><i class="bi bi-telephone"></i></div>
                        <div>
                            <div class="ci-label">Phone</div>
                            <div class="ci-value">(056) 000-0000</div>
                        </div>
                    </div>
                    <div class="contact-info-row">
                        <div class="contact-info-icon"><i class="bi bi-clock"></i></div>
                        <div>
                            <div class="ci-label">Office Hours</div>
                            <div class="ci-value">Mon &ndash; Fri, 8:00 AM &ndash; 5:00 PM</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="contact-form-card reveal reveal-delay-2">
                    <h5 class="fw-800 mb-3">Send a Message</h5>

                    <div id="contactFormFeedback" class="alert alert-success d-none" role="alert">
                        <i class="bi bi-check-circle me-1"></i> Message sent. The office will get back to you shortly.
                    </div>

                    <form id="famsContactForm" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="contactName" placeholder="Your Name" required>
                            <label for="contactName">Your Name</label>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="contactEmail" placeholder="Your Email" required>
                            <label for="contactEmail">Your Email</label>
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="contactMessage" placeholder="Your Message" style="height: 130px" required></textarea>
                            <label for="contactMessage">Your Message</label>
                            <div class="invalid-feedback">Please enter a message.</div>
                        </div>
                        <button type="submit" class="btn btn-fams-primary w-100">
                            <i class="bi bi-send me-1"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="map-placeholder reveal reveal-delay-3">
                    <i class="bi bi-map"></i>
                    <div class="fw-700" style="font-weight:700;">Bulan, Sorsogon</div>
                    <small>Interactive Google Map integration point &mdash; embed the office location here.</small>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================
     Footer
     ========================================================= --}}
<footer class="fams-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand">
                    <span class="fams-brand-mark"><i class="bi bi-flower2"></i></span>
                    <span>
                        <span class="brand-main d-block">FAMS</span>
                        <span class="brand-sub">Farmer Assistance Management System</span>
                    </span>
                </div>
                <p class="footer-desc">A decision support platform helping the Municipal Agriculture Office serve
                    farmers with better data, faster monitoring, and transparent program delivery.</p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" aria-label="Email"><i class="bi bi-envelope"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <h6 class="footer-heading">Quick Links</h6>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#weather">Weather</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-6">
                <h6 class="footer-heading">Useful Links</h6>
                <ul>
                    <li><a href="{{ Route::has('login') ? route('login') : '#' }}">Farmer Login</a></li>
                    <li><a href="{{ Route::has('register') ? route('register') : '#' }}">Register</a></li>
                    <li><a href="#">Assistance Programs</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Use</a></li>
                </ul>
            </div>

            <div class="col-lg-3">
                <h6 class="footer-heading">Contact Info</h6>
                <div class="footer-contact-item">
                    <i class="bi bi-geo-alt"></i>
                    <span>Municipal Hall, Bulan, Sorsogon</span>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-envelope"></i>
                    <span>agriculture.bulan@sorsogon.gov.ph</span>
                </div>
                <div class="footer-contact-item">
                    <i class="bi bi-telephone"></i>
                    <span>(056) 000-0000</span>
                </div>
            </div>
        </div>

        <div class="footer-bottom text-center">
            &copy; {{ date('Y') }} Farmer Assistance Management System (FAMS). All Rights Reserved.
        </div>
    </div>
</footer>

<button class="btn-back-to-top" id="backToTop" aria-label="Back to top">
    <i class="bi bi-arrow-up"></i>
</button>

{{-- Bootstrap 5.3 JS Bundle --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- FAMS custom script --}}
<script src="{{ asset('js/homepage.js') }}"></script>
</body>
</html>
<?php
// Set timezone to Manila time (UTC+8)
date_default_timezone_set('Asia/Manila');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Harley - Professional Time Tracking System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #00B8A9 0%, #00CFFF 100%);
      --secondary-gradient: linear-gradient(135deg, #10B981 0%, #3B82F6 100%);
      --accent-gradient: linear-gradient(135deg, #06D6A0 0%, #118AB2 100%);
      --dark-gradient: linear-gradient(135deg, #065F46 0%, #1E3A8A 100%);
    }

    * {
      scroll-behavior: smooth;
    }

    html {
      font-family: 'Inter', sans-serif;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #00B8A9 0%, #00CFFF 100%);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #00CFFF 0%, #00B8A9 100%);
    }

    /* Gradient text utility */
    .gradient-text {
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .gradient-text-secondary {
      background: var(--secondary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Advanced gradient buttons */
    .btn-gradient {
      background: var(--primary-gradient);
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .btn-gradient:before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }

    .btn-gradient:hover:before {
      left: 100%;
    }

    .btn-gradient:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 184, 169, 0.4);
    }

    /* Hero section with animated background */
    #hero {
      background: var(--primary-gradient);
      border-radius: 2rem;
      color: white;
      position: relative;
      overflow: hidden;
    }

    #hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/><circle cx="900" cy="800" r="80" fill="url(%23a)"/></svg>');
      animation: float 20s ease-in-out infinite;
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0px) rotate(0deg);
      }

      50% {
        transform: translateY(-20px) rotate(180deg);
      }
    }

    /* Card hover effects */
    .feature-card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid transparent;
      background: linear-gradient(white, white) padding-box,
        var(--primary-gradient) border-box;
    }

    .feature-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 40px rgba(0, 184, 169, 0.15);
      border: 1px solid transparent;
    }

    .feature-card .icon {
      transition: all 0.3s ease;
    }

    .feature-card:hover .icon {
      transform: scale(1.1) rotate(5deg);
      color: #00B8A9;
    }

    /* Parallax effect */
    .parallax {
      transform: translateZ(0);
      will-change: transform;
    }

    /* Glassmorphism effect */
    .glass {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Animated underline */
    .animated-underline {
      position: relative;
    }

    .animated-underline::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 0;
      background: var(--primary-gradient);
      transition: width 0.3s ease;
    }

    .animated-underline:hover::after {
      width: 100%;
    }

    /* Floating animation */
    @keyframes floating {
      0% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(-10px);
      }

      100% {
        transform: translateY(0px);
      }
    }

    .floating {
      animation: floating 3s ease-in-out infinite;
    }

    /* Pulse animation */
    .pulse-glow {
      animation: pulse-glow 2s infinite;
    }

    @keyframes pulse-glow {
      0% {
        box-shadow: 0 0 0 0 rgba(0, 184, 169, 0.4);
      }

      70% {
        box-shadow: 0 0 0 10px rgba(0, 184, 169, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(0, 184, 169, 0);
      }
    }

    /* Loading shimmer effect */
    .shimmer {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
      0% {
        background-position: -200% 0;
      }

      100% {
        background-position: 200% 0;
      }
    }

    /* Team member card effects */
    .team-card {
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .team-card:hover {
      transform: translateY(-10px) rotateY(5deg);
    }

    .team-card img {
      transition: all 0.3s ease;
      filter: grayscale(20%);
    }

    .team-card:hover img {
      filter: grayscale(0%);
      transform: scale(1.1);
    }

    /* Navbar blur effect */
    .navbar-blur {
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      background: rgba(255, 255, 255, 0.9);
    }

    /* Section reveal animation */
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.6s ease;
    }

    .reveal.active {
      opacity: 1;
      transform: translateY(0);
    }

    /* Custom button variants */
    .btn-outline {
      border: 2px solid;
      border-image: var(--primary-gradient) 1;
      background: transparent;
      color: #00B8A9;
      transition: all 0.3s ease;
    }

    .btn-outline:hover {
      background: var(--primary-gradient);
      color: white;
      transform: translateY(-2px);
    }
  </style>
</head>
<<body class="bg-gray-50 text-gray-700 leading-relaxed">

  <!-- Header Navigation with enhanced blur effect -->
  <header class="fixed top-0 w-full navbar-blur shadow-lg z-50 transition-all duration-300">
    <nav class="max-w-7xl mx-auto px-6 md:px-8 flex items-center justify-between h-20">
      <a href="#hero" class="flex items-center text-gray-900 hover:text-gray-700 transition-all duration-300 group">
        <div class="relative">
          <img src="https://resourcestaff.com.au/wp-content/uploads/2023/02/RSS-logo-colour.svg"
            alt="Company Logo"
            class="h-14 w-14 mr-4 rounded-xl shadow-md transition-transform duration-300 group-hover:scale-110" />
          <div class="absolute inset-0 bg-gradient-to-r from-purple-400 to-blue-500 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
        </div>
        <span class="text-3xl font-bold gradient-text">Harley</span>
      </a>

      <ul class="hidden md:flex space-x-10 text-gray-700 font-medium">
        <li><a href="#guide" class="animated-underline hover:text-gray-900 transition-colors duration-300 text-lg">Guide</a></li>
        <li><a href="#about" class="animated-underline hover:text-gray-900 transition-colors duration-300 text-lg">About</a></li>
        <li><a href="#creator" class="animated-underline hover:text-gray-900 transition-colors duration-300 text-lg">Team</a></li>
      </ul>

      <a href="Public/employee/login.php"
        class="hidden md:inline-block px-8 py-3 btn-gradient text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all duration-300 pulse-glow">
        Login
      </a>

      <button id="mobile-menu-button" class="md:hidden focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition-colors duration-300" aria-label="Open Menu">
        <svg class="w-8 h-8 text-gray-700 transition-transform duration-300" id="menu-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          viewBox="0 0 -24 24">
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
      </button>
    </nav>

    <div id="mobile-menu" class="md:hidden glass shadow-xl hidden transform transition-all duration-300 ease-in-out">
      <ul class="space-y-6 px-8 py-8 text-gray-700 font-medium">
        <li><a href="#guide" class="block hover:text-gray-900 text-lg transition-colors duration-300">Guide</a></li>
        <li><a href="#about" class="block hover:text-gray-900 text-lg transition-colors duration-300">About</a></li>
        <li><a href="#creator" class="block hover:text-gray-900 text-lg transition-colors duration-300">Team</a></li>
        <li><a href="Public/employee/login.php"
            class="block px-6 py-3 btn-gradient text-white rounded-full text-center font-semibold hover:shadow-lg transition-all duration-300">
            Get Started
          </a></li>
      </ul>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 md:px-8 pt-20">

    <!-- Hero Section with enhanced animations -->
    <section id="hero" class="pt-24 pb-32 text-center max-w-5xl mx-auto px-12 shadow-2xl reveal relative z-10 mb-20">
      <div class="relative z-10">
        <h1 class="text-6xl md:text-7xl font-extrabold text-white mb-8 animate__animated animate__fadeInUp floating">
          Welcome to Harley
        </h1>
        <p class="text-xl md:text-2xl mb-12 max-w-3xl mx-auto text-white/90 animate__animated animate__fadeInUp animate__delay-1s">
          Experience the future of time tracking with our cutting-edge system designed for modern professionals
        </p>
        <div class="flex flex-col sm:flex-row gap-6 justify-center items-center animate__animated animate__fadeInUp animate__delay-2s">
          <a href="Public/employee/login.php"
            class="inline-block px-12 py-4 btn-gradient text-white font-semibold rounded-full shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:scale-105 text-lg">
            Start Time in
          </a>
          <a href="#guide"
            class="inline-block px-12 py-4 btn-gradient text-white font-semibold rounded-full shadow-2xl hover:shadow-3xl transition-all duration-300 transform hover:scale-105 text-lg">
            Learn More
          </a>
        </div>
      </div>
    </section>

    <!-- Features Overview Section -->
    <section class="py-20 reveal">
      <div class="text-center mb-16">
        <h2 class="text-5xl font-bold gradient-text mb-6">Why Choose Harley?</h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">Discover the powerful features that make time tracking effortless and efficient</p>
      </div>

      <div class="grid gap-8 md:grid-cols-3 mb-20">
        <div class="feature-card bg-white rounded-2xl shadow-lg p-8 text-center">
          <div class="w-16 h-16 mx-auto mb-6 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Lightning Fast</h3>
          <p class="text-gray-600">Clock in and out in seconds with our optimized interface</p>
        </div>

        <div class="feature-card bg-white rounded-2xl shadow-lg p-8 text-center">
          <div class="w-16 h-16 mx-auto mb-6 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">100% Accurate</h3>
          <p class="text-gray-600">Precise time tracking with automatic synchronization</p>
        </div>

        <div class="feature-card bg-white rounded-2xl shadow-lg p-8 text-center">
          <div class="w-16 h-16 mx-auto mb-6 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Secure & Reliable</h3>
          <p class="text-gray-600">Enterprise-grade security protecting your data</p>
        </div>
      </div>
    </section>

    <!-- Guide Section with enhanced cards -->
    <section id="guide" class="py-20 reveal">
      <div class="text-center mb-16">
        <h2 class="text-5xl font-bold gradient-text mb-6">How It Works</h2>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">Follow these simple steps to master your time tracking workflow</p>
      </div>

      <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-4">

        <!-- Step 1 -->
        <article class="feature-card bg-white rounded-2xl shadow-xl p-10 flex flex-col items-center text-center relative overflow-hidden">
          <div class="absolute top-4 right-4 w-8 h-8 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-bold text-sm">1</div>
          <div class="w-20 h-20 mb-8 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 6v6l4 2" />
              <circle cx="12" cy="12" r="10" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Clock In When You Start</h3>
          <p class="text-gray-600 leading-relaxed">Use our intuitive interface to log your start time with a single click. Smart detection ensures accuracy every time.</p>
        </article>

        <!-- Step 2 -->
        <article class="feature-card bg-white rounded-2xl shadow-xl p-10 flex flex-col items-center text-center relative overflow-hidden">
          <div class="absolute top-4 right-4 w-8 h-8 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
          <div class="w-20 h-20 mb-8 bg-gradient-to-r from-teal-500 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 17v-6a2 2 0 012-2h3" />
              <path d="M9 17h6" />
              <circle cx="12" cy="12" r="10" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Clock Out When You Finish</h3>
          <p class="text-gray-600 leading-relaxed">Seamlessly record your end time to complete your work session. Automatic calculations handle the rest.</p>
        </article>

        <!-- Step 3 -->
        <article class="feature-card bg-white rounded-2xl shadow-xl p-10 flex flex-col items-center text-center relative overflow-hidden">
          <div class="absolute top-4 right-4 w-8 h-8 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
          <div class="w-20 h-20 mb-8 bg-gradient-to-r from-green-500 to-cyan-400 rounded-2xl flex items-center justify-center shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 7h16M4 12h8m-4 5h8" />
              <rect width="14" height="10" x="5" y="7" rx="2" ry="2" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Keep Accurate Records</h3>
          <p class="text-gray-600 leading-relaxed">All your time entries are automatically saved and organized for easy access and reporting.</p>
        </article>

        <!-- Step 4 -->
        <article class="feature-card bg-white rounded-2xl shadow-xl p-10 flex flex-col items-center text-center relative overflow-hidden">
          <div class="absolute top-4 right-4 w-8 h-8 bg-gradient-to-r from-emerald-600 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">4</div>
          <div class="w-20 h-20 mb-8 bg-gradient-to-r from-emerald-600 to-blue-500 rounded-2xl flex items-center justify-center shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 8v4l3 3" />
              <circle cx="12" cy="12" r="10" />
              <circle cx="17" cy="17" r="3" />
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">Enjoy Seamless Tracking</h3>
          <p class="text-gray-600 leading-relaxed">Experience effortless time management with our advanced analytics and insights dashboard.</p>
        </article>

      </div>
    </section>

    <!-- About Section with enhanced design -->
    <section id="about" class="py-20 reveal">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-5xl font-bold gradient-text mb-6">About the System</h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">Built with modern technology and designed for the future of work</p>
        </div>

        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div class="space-y-8">
            <div class="bg-white rounded-2xl p-8 shadow-lg feature-card">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-xl flex items-center justify-center mr-4">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900">Precision Tracking</h3>
              </div>
              <p class="text-gray-600 leading-relaxed">Advanced algorithms ensure your time is tracked with millisecond accuracy, giving you confidence in every record.</p>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg feature-card">
              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-teal-500 to-blue-500 rounded-xl flex items-center justify-center mr-4">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900">Lightning Performance</h3>
              </div>
              <p class="text-gray-600 leading-relaxed">Optimized for speed and efficiency, our system responds instantly to your actions without any delays.</p>
            </div>
          </div>

          <div class="relative">
            <div class="bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-3xl p-12 text-white text-center shadow-2xl">
              <div class="absolute inset-0 bg-black opacity-10 rounded-3xl"></div>
              <div class="relative z-10">
                <h3 class="text-4xl font-bold mb-6">Streamlining Our Team's Workflow</h3>
                <p class="text-xl mb-8 opacity-90">Built by Resourcestaffing Solutions for our team's efficient time management</p>
                <div class="grid grid-cols-3 gap-6 text-center">
                  <div>
                    <div class="text-3xl font-bold">RSS</div>
                    <div class="text-sm opacity-75">Team Solution</div>
                  </div>
                  <div>
                    <div class="text-3xl font-bold">2025</div>
                    <div class="text-sm opacity-75">Latest Version</div>
                  </div>
                  <div>
                    <div class="text-3xl font-bold">24/7</div>
                    <div class="text-sm opacity-75">Reliability</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Team Section with enhanced design -->
    <section id="creator" class="py-20 bg-gradient-to-b from-gray-50 to-white reveal">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-5xl font-bold gradient-text mb-6">Meet Our Amazing Team</h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            This system was crafted by a dedicated team of professionals who believe in making time tracking effortless and precise.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 justify-items-center mb-16">
          <!-- Cedrick -->
          <div class="team-card bg-white rounded-3xl shadow-xl p-8 text-center max-w-sm w-full">
            <div class="relative mb-6">
              <img src="https://media.licdn.com/dms/image/v2/D5603AQFbp-i49k27Dw/profile-displayphoto-shrink_200_200/B56Zfuwkv5G0AY-/0/1752057402126?e=1756944000&v=beta&t=jWQvv-YGf4niIP_7wzJ-39lG50-AJ51Cp5A2Hz0MrOI"
                alt="Cedrick"
                class="w-24 h-24 rounded-full shadow-lg mx-auto object-cover" />
              <div class="absolute inset-0 w-24 h-24 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 opacity-0 hover:opacity-20 transition-opacity duration-300 mx-auto"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Cedrick Arnigo</h3>
            <p class="text-gray-600 text-sm mb-4">IT Support Specialist / Backend Dev</p>
            <div class="w-16 h-1 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-full mx-auto"></div>
          </div>

          <!-- Neil (middle) -->
          <div class="team-card bg-white rounded-3xl shadow-xl p-8 text-center max-w-sm w-full">
            <div class="relative mb-6">
              <img src="https://media.licdn.com/dms/image/v2/D5603AQHL6O6GAuXZcw/profile-displayphoto-shrink_800_800/profile-displayphoto-shrink_800_800/0/1723794860120?e=1755129600&v=beta&t=ndvIzsGQTfTiagNITgbnjmdh7aTErmYJpeQhKWqdEdM"
                alt="Neil Costelloe"
                class="w-24 h-24 rounded-full shadow-lg mx-auto object-cover" />
              <div class="absolute inset-0 w-24 h-24 rounded-full bg-gradient-to-r from-green-500 to-cyan-400 opacity-0 hover:opacity-20 transition-opacity duration-300 mx-auto"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Neil Costelloe</h3>
            <p class="text-gray-600 text-sm mb-4">General Manager</p>
            <div class="w-16 h-1 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full mx-auto"></div>
          </div>

          <!-- Resty J -->
          <div class="team-card bg-white rounded-3xl shadow-xl p-8 text-center max-w-sm w-full">
            <div class="relative mb-6">
              <img src="https://media.licdn.com/dms/image/v2/D4D35AQFoRrmDZTkEzA/profile-framedphoto-shrink_200_200/B4DZcWPpl_GwAc-/0/1748424891079?e=1754362800&v=beta&t=fLnSSgKQ0VYjlUQb0jY42N7BYR_n9vEyvxjooAHXLxw"
                alt="Resty James Nazareno"
                class="w-24 h-24 rounded-full shadow-lg mx-auto object-cover" />
              <div class="absolute inset-0 w-24 h-24 rounded-full bg-gradient-to-r from-teal-500 to-blue-500 opacity-0 hover:opacity-20 transition-opacity duration-300 mx-auto"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Resty James Nazareno</h3>
            <p class="text-gray-600 text-sm mb-4">IT Intern / Web Developer</p>
            <div class="w-16 h-1 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full mx-auto"></div>
          </div>

          <!-- Rica -->
          <div class="team-card bg-white rounded-3xl shadow-xl p-8 text-center max-w-sm w-full">
            <div class="relative mb-6">
              <img src="https://media.licdn.com/dms/image/v2/D5635AQENR94apYEyzA/profile-framedphoto-shrink_200_200/B56Zg63S3XG4AY-/0/1753334230710?e=1754362800&v=beta&t=amAJKKLJ51wZ0n7EYzqmJnRfuMDFTiNCuifrZrL7xbQ"
                alt="Rica Joy Tolomia"
                class="w-24 h-24 rounded-full shadow-lg mx-auto object-cover" />
              <div class="absolute inset-0 w-24 h-24 rounded-full bg-gradient-to-r from-green-500 to-cyan-400 opacity-0 hover:opacity-20 transition-opacity duration-300 mx-auto"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Rica Joy Tolomia</h3>
            <p class="text-gray-600 text-sm mb-4">TA/HR Specialist</p>
            <div class="w-16 h-1 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full mx-auto"></div>
          </div>

          <!-- Tina -->
          <div class="team-card bg-white rounded-3xl shadow-xl p-8 text-center max-w-sm w-full">
            <div class="relative mb-6">
              <img src="https://media.licdn.com/dms/image/v2/D5603AQHHiQ3TgLMiwQ/profile-displayphoto-shrink_400_400/B56ZNlwwj.GoAg-/0/1732579102178?e=1755129600&v=beta&t=wSuiQS2o57BXUTTpCa75W8pOT4drLMdW_g0AnLDcSok"
                alt="Tina Pangan"
                class="w-24 h-24 rounded-full shadow-lg mx-auto object-cover" />
              <div class="absolute inset-0 w-24 h-24 rounded-full bg-gradient-to-r from-green-500 to-cyan-400 opacity-0 hover:opacity-20 transition-opacity duration-300 mx-auto"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Tina Pangan</h3>
            <p class="text-gray-600 text-sm mb-4">Executive Assistant to the General Manager</p>
            <div class="w-16 h-1 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full mx-auto"></div>
          </div>

          <!-- Felicci (Peach) -->
          <div class="team-card bg-white rounded-3xl shadow-xl p-8 text-center max-w-sm w-full">
            <div class="relative mb-6">
              <img src="https://scontent.fcrk1-3.fna.fbcdn.net/v/t39.30808-6/495543582_668588906153630_3362198586390250713_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeFOKLIP2lDBZnQtZREdU1QGJA0afOfO9PIkDRp858708tw_nR7HK4T1Fv3z3q8ujKPIeBS0RK7_niTKh0Ep-1SJ&_nc_ohc=UwIqpLYyTaMQ7kNvwGOD8iE&_nc_oc=Adk6McHt8irOH-3gtEpRcGhZKGCuJq2V-PkytXLWu2x_kU3WGvORLpdbp__pz8KbtbM&_nc_zt=23&_nc_ht=scontent.fcrk1-3.fna&_nc_gid=amfXA1LYdybNnf-2BbG2zw&oh=00_AfSZm95ukoVo48vDa9SsYM78xFoNf3ZmUQ21ij-WuD5YIw&oe=688E2976"
                alt="Felicci Herera"
                class="w-24 h-24 rounded-full shadow-lg mx-auto object-cover" />
              <div class="absolute inset-0 w-24 h-24 rounded-full bg-gradient-to-r from-green-500 to-cyan-400 opacity-0 hover:opacity-20 transition-opacity duration-300 mx-auto"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Felicci Herera</h3>
            <p class="text-gray-600 text-sm mb-4">HR/Admin Intern</p>
            <div class="w-16 h-1 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full mx-auto"></div>
          </div>
        </div>

        <p class="max-w-2xl text-center text-gray-700 italic mt-12 mx-auto">
          Behind every line of code is a team that cared, a group of passionate individuals who brought their skills, insights, and time to turn an idea into a fully working system. From brainstorming sessions to testing, refining, and implementing features, this project is the result of true collaboration. Cedrick and RJ led the technical foundation with their commitment and late-night problem-solving, while Tina, Neil, and Rica offered constant support, feedback, and direction that helped shape the system to what it is today.
          <br><br>
          Special thanks to RJ for pushing major updates that elevated the platform’s performance and usability. This system is more than a tool, it's a reflection of teamwork, learning, and shared purpose. We’re proud of what we’ve created together.
        </p>

    </section>

  </main>

  <!-- Footer Section -->
  <footer class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-16">
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid md:grid-cols-4 gap-12 mb-12">
        <div class="md:col-span-2">
          <div class="flex items-center mb-6">
            <img src="https://resourcestaff.com.au/wp-content/uploads/2023/02/RSS-logo-colour.svg"
              alt="Company Logo"
              class="h-12 w-12 mr-4 rounded-xl" />
            <span class="text-3xl font-bold">Harley</span>
          </div>
          <p class="text-gray-300 text-lg leading-relaxed mb-6">
            Professional time tracking solution designed for modern workplaces.
            Accurate, efficient, and user-friendly.
          </p>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-cyan-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-300">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
              </svg>
            </a>
            <a href="#" class="w-10 h-10 bg-gradient-to-r from-teal-500 to-blue-500 rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-300">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
              </svg>
            </a>
            <a href="#" class="w-10 h-10 bg-gradient-to-r from-green-500 to-cyan-400 rounded-full flex items-center justify-center hover:scale-110 transition-transform duration-300">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.097.118.112.223.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.747 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z" />
              </svg>
            </a>
          </div>
        </div>

        <div>
          <h3 class="text-xl font-bold mb-6">Quick Links</h3>
          <ul class="space-y-3">
            <li><a href="#guide" class="text-gray-300 hover:text-white transition-colors duration-300 hover:translate-x-2 inline-block">How It Works</a></li>
            <li><a href="#about" class="text-gray-300 hover:text-white transition-colors duration-300 hover:translate-x-2 inline-block">About</a></li>
            <li><a href="#creator" class="text-gray-300 hover:text-white transition-colors duration-300 hover:translate-x-2 inline-block">Our Team</a></li>
            <li><a href="Public/employee/login.php" class="text-gray-300 hover:text-white transition-colors duration-300 hover:translate-x-2 inline-block">Login</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-xl font-bold mb-6">Contact Info</h3>
          <ul class="space-y-3 text-gray-300">
            <li class="flex items-center">
              <svg class="w-5 h-5 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              https://resourcestaff.com.au/
            </li>
            <li class="flex items-center">
              <svg class="w-5 h-5 mr-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              045 499 5308
            </li>
            <li class="flex items-center">
              <svg class="w-5 h-5 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Philippines HQ
              4th Floor
              Clark Centre 10 Berthaphil
              Jose Abad Santos Ave
              Clark Freeport Zone
              Pampanga Philippines 2009
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-gray-700 pt-8 text-center">
        <p class="text-gray-400">
          &copy; 2025 Harley Time Tracking System. Built with ❤️ by the Resource Staff Solutions team.
        </p>
      </div>
    </div>
  </footer>

  <script>
    // Enhanced Mobile menu toggle with animation
    const btn = document.getElementById('mobile-menu-button');
    const menu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');

    btn.addEventListener('click', () => {
      menu.classList.toggle('hidden');
      btn.classList.toggle('rotate-90');

      // Animate menu icon
      if (menu.classList.contains('hidden')) {
        menuIcon.innerHTML = '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>';
      } else {
        menuIcon.innerHTML = '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>';
      }
    });

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Intersection Observer for reveal animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
        }
      });
    }, observerOptions);

    // Observe all elements with reveal class
    document.querySelectorAll('.reveal').forEach(el => {
      observer.observe(el);
    });

    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      const parallaxElements = document.querySelectorAll('.parallax');

      parallaxElements.forEach(element => {
        const speed = element.dataset.speed || 0.5;
        const yPos = -(scrolled * speed);
        element.style.transform = `translateY(${yPos}px)`;
      });
    });

    // Dynamic navbar background
    window.addEventListener('scroll', () => {
      const navbar = document.querySelector('header');
      if (window.scrollY > 100) {
        navbar.classList.add('shadow-2xl');
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
      } else {
        navbar.classList.remove('shadow-2xl');
        navbar.style.background = 'rgba(255, 255, 255, 0.9)';
      }
    });

    // Loading animation for images
    document.querySelectorAll('img').forEach(img => {
      img.addEventListener('load', function() {
        this.style.opacity = '1';
        this.style.transform = 'scale(1)';
      });

      // Initial state
      img.style.opacity = '0';
      img.style.transform = 'scale(0.9)';
      img.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    // Typing effect for hero title (if needed)
    function typeWriter(element, text, speed = 100) {
      let i = 0;
      element.innerHTML = '';

      function type() {
        if (i < text.length) {
          element.innerHTML += text.charAt(i);
          i++;
          setTimeout(type, speed);
        }
      }
      type();
    }

    // Enhanced button interactions
    document.querySelectorAll('.btn-gradient, .btn-outline').forEach(button => {
      button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px) scale(1.05)';
      });

      button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
      });

      button.addEventListener('mousedown', function() {
        this.style.transform = 'translateY(-1px) scale(0.98)';
      });

      button.addEventListener('mouseup', function() {
        this.style.transform = 'translateY(-3px) scale(1.05)';
      });
    });

    // Feature cards stagger animation
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
      card.style.animationDelay = `${index * 0.1}s`;
      card.classList.add('animate__animated', 'animate__fadeInUp');
    });

    // Team cards hover sound effect (optional)
    const teamCards = document.querySelectorAll('.team-card');
    teamCards.forEach(card => {
      card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-10px) rotateY(5deg) scale(1.02)';
      });

      card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0) rotateY(0) scale(1)';
      });
    });

    // Initialize animations on load
    document.addEventListener('DOMContentLoaded', () => {
      // Add entrance animations
      setTimeout(() => {
        document.body.style.opacity = '1';
      }, 100);

      // Stagger reveal animations
      const reveals = document.querySelectorAll('.reveal');
      reveals.forEach((reveal, index) => {
        setTimeout(() => {
          reveal.style.opacity = '1';
          reveal.style.transform = 'translateY(0)';
        }, index * 200);
      });
    });

    // Performance optimization
    let ticking = false;

    function updateScrollEffects() {
      // Batch scroll effects
      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(updateScrollEffects);
        ticking = true;
      }
    });
  </script>
  </body>

</html>

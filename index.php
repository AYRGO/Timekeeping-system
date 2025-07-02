<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Harley</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@600;700&display=swap');
    html {
      font-family: 'Inter', sans-serif;
    }
    /* Gradient text utility */
    .gradient-text {
      background: linear-gradient(90deg, #00B8A9, #00CFFF);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    /* Gradient button */
    .btn-gradient {
      background-image: linear-gradient(90deg, #00B8A9, #00CFFF);
    }
    /* Hero background gradient container */
    #hero {
      background: linear-gradient(135deg, #00B8A9 0%, #00CFFF 100%);
      border-radius: 1.5rem;
      color: white;
    }
  </style>
</head>
<body class="bg-white text-gray-600 leading-relaxed scroll-smooth">

  <!-- Header Navigation -->
  <header class="sticky top-0 bg-white shadow-md z-30">
    <nav class="max-w-7xl mx-auto px-6 md:px-8 flex items-center justify-between h-16">
      <a href="#hero" class="flex items-center text-gray-900 hover:text-gray-700 transition">
        <!-- Logo image placeholder: Replace with your own logo image -->
        <img src="https://resourcestaff.com.au/wp-content/uploads/2023/02/RSS-logo-colour.svg" alt="Company Logo" class="h-12 w-12 mr-3 rounded" />
        <span class="text-2xl font-semibold text-gray-900">Harley</span>
      </a>
      <ul class="hidden md:flex space-x-8 text-gray-600 font-medium">
        <li><a href="#guide" class="hover:text-gray-900 transition">Guide</a></li>
        <li><a href="#about" class="hover:text-gray-900 transition">About</a></li>
        <li><a href="#creator" class="hover:text-gray-900 transition">Creator</a></li>
      </ul>
      <a href="https://timekeeping-system.resourcestaffonline.com/employee/login" class="hidden md:inline-block px-5 py-2 btn-gradient text-white font-semibold rounded-md shadow-md hover:brightness-110 transition">Get Started</a>
      <button id="mobile-menu-button" class="md:hidden focus:outline-none" aria-label="Open Menu">
        <svg class="w-7 h-7 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
          viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </nav>
    <div id="mobile-menu" class="md:hidden bg-white shadow-lg hidden">
      <ul class="space-y-4 px-6 py-6 text-gray-700 font-medium">
        <li><a href="#guide" class="block hover:text-gray-900">Guide</a></li>
        <li><a href="#about" class="block hover:text-gray-900">About</a></li>
        <li><a href="#creator" class="block hover:text-gray-900">Creator</a></li>
        <li><a href="https://harley.resourcestaffonline.com/Public/employee/login.php" class="block px-4 py-2 btn-gradient text-white rounded-md text-center font-semibold hover:brightness-110 transition">Get Started</a></li>
      </ul>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 md:px-8">

    <!-- Hero Section -->
    <section id="hero" class="pt-20 pb-28 text-center max-w-4xl mx-auto px-10 shadow-lg">
      <h1 class="text-5xl md:text-6xl font-extrabold text-white/90 leading-tight mb-6">Welcome to Harley</h1>
      <p class="text-lg md:text-xl mb-8 max-w-prose mx-auto text-white/90">Effortlessly track your work hours and keep accurate time records with ease.</p>
      <a href="https://harley.resourcestaffonline.com/Public/employee/login.php" class="inline-block px-10 py-4 btn-gradient text-white font-semibold rounded-xl shadow-lg hover:brightness-110 transition-transform transform hover:scale-105">Get Started</a>
    </section>

    <!-- Guide Section -->
    <section id="guide" class="pt-16 pb-20">
      <h2 class="text-3xl font-semibold text-gray-900 mb-12 text-center">How It Works</h2>
      <div class="grid gap-10 md:grid-cols-4 md:gap-6">

        <!-- Step 1 -->
        <article class="bg-white rounded-xl shadow-md p-8 flex flex-col items-center text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-gray-800 mb-6 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 6v6l4 2" />
            <circle cx="12" cy="12" r="10" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">1. Clock In When You Start</h3>
          <p class="text-gray-600 max-w-xs">Use the simple interface to log your start time quickly and easily.</p>
        </article>

        <!-- Step 2 -->
        <article class="bg-white rounded-xl shadow-md p-8 flex flex-col items-center text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-gray-800 mb-6 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 17v-6a2 2 0 012-2h3" />
            <path d="M9 17h6" />
            <circle cx="12" cy="12" r="10" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">2. Clock Out When You Finish</h3>
          <p class="text-gray-600 max-w-xs">Easily record your end time to ensure your hours are logged accurately.</p>
        </article>

        <!-- Step 3 -->
        <article class="bg-white rounded-xl shadow-md p-8 flex flex-col items-center text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-gray-800 mb-6 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 7h16M4 12h8m-4 5h8" />
            <rect width="14" height="10" x="5" y="7" rx="2" ry="2" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">3. Keep Accurate Time Records</h3>
          <p class="text-gray-600 max-w-xs">Your clock-in and clock-out times are automatically recorded for accuracy.</p>
        </article>

        <!-- Step 4 -->
        <article class="bg-white rounded-xl shadow-md p-8 flex flex-col items-center text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-gray-800 mb-6 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 8v4l3 3" />
            <circle cx="12" cy="12" r="10" />
            <circle cx="17" cy="17" r="3" />
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">4. Enjoy Seamless Time Tracking</h3>
          <p class="text-gray-600 max-w-xs">Experience a hassle-free way to manage your time without any complications.</p>
        </article>

      </div>
    </section>

    <!-- About Section -->
    <section id="about" class="pt-16 pb-20 max-w-3xl mx-auto">
      <h2 class="text-3xl font-semibold text-gray-900 mb-6 text-center">About the System</h2>
      <p class="text-center text-gray-600 text-lg leading-relaxed">
        Harley is built to help you effortlessly track your work hours with precision and simplicity. It offers features like <strong>easy clock-in and clock-out</strong> functionality and accurate time recording to help you manage your time effectively.
        Stay focused on your work while the system manages your time data accurately and reliably.
      </p>
    </section>

   <!-- Creator Section -->
<section id="creator" class="pt-16 pb-20 border-t border-gray-100 max-w-6xl mx-auto px-6">
  <div class="text-center mb-12">
    <p class="text-gray-600 max-w-2xl mx-auto text-lg">
      This system was built to make time-tracking easier and more efficient. Accurate time management is key to success, and with the help of this great team, it came to life.
    </p>
  </div>

  <div class="flex flex-wrap justify-center gap-10 text-center">
    <!-- Cedrick -->
    <div class="w-[180px]">
      <img src="https://media.licdn.com/dms/image/v2/D5603AQHmxHLdT1l1qg/profile-displayphoto-shrink_800_800/B56ZdpqUhqGQAc-/0/1749824394509?e=1755734400&v=beta&t=PHoW1jkC5RUvi0EVJ4z1VkD6BUMxmkjbCxN37NfdIpg" alt="Cedrick" class="rounded-full shadow-md mb-3 mx-auto" width="96" height="96" />
      <p class="font-semibold text-gray-900">Cedrick</p>
      <p class="text-gray-600 text-sm">IT Support Specialist / Backend Dev</p>
    </div>

    <!-- Resty J -->
    <div class="w-[180px]">
      <img src="https://scontent.fmnl30-2.fna.fbcdn.net/v/t39.30808-1/427956383_7053264681460812_1953578908426368961_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=111&ccb=1-7&_nc_sid=1d2534&_nc_eui2=AeGuN5xFGPMEaytxfVxy7ibU_aHJ8kZGEin9ocnyRkYSKSwB9diglzYEbjVPnhgwGrtyFHVV1MUGerU_CS-nNanD&_nc_ohc=LZvuoT_rUzwQ7kNvwEd62RT&_nc_oc=AdnurrqUNgtMIxaa3SH6k7G2KoTYpvj6qWQu0iGVq7YExEFqPPdxHKn8Jk7oRK9Lc-Y&_nc_zt=24&_nc_ht=scontent.fmnl30-2.fna&_nc_gid=JMLV7zALRqqSWvTlr1vnyA&oh=00_AfNTquwphe0GbaspT35rq09Wn5eZT00vquGZ5a42ALNVVw&oe=686A59EA" alt="Resty James Nazareno" class="rounded-full shadow-md mb-3 mx-auto" width="96" height="96" />
      <p class="font-semibold text-gray-900">Resty James Nazareno</p>
      <p class="text-gray-600 text-sm">IT Intern / Web Developer</p>
    </div>
    
       <!-- Neil -->
    <div class="w-[180px]">
      <img src="https://media.licdn.com/dms/image/v2/D5603AQHL6O6GAuXZcw/profile-displayphoto-shrink_800_800/profile-displayphoto-shrink_800_800/0/1723794860120?e=1755129600&v=beta&t=ndvIzsGQTfTiagNITgbnjmdh7aTErmYJpeQhKWqdEdM" alt="Neil Costelloe" class="rounded-full shadow-md mb-3 mx-auto" width="96" height="96" />
      <p class="font-semibold text-gray-900">Neil Costelloe</p>
      <p class="text-gray-600 text-sm">General Manager</p>
    </div>


    <!-- Tina -->
    <div class="w-[180px]">
      <img src="https://media.licdn.com/dms/image/v2/D5603AQHHiQ3TgLMiwQ/profile-displayphoto-shrink_400_400/B56ZNlwwj.GoAg-/0/1732579102178?e=1755129600&v=beta&t=wSuiQS2o57BXUTTpCa75W8pOT4drLMdW_g0AnLDcSok" alt="Tina Pangan" class="rounded-full shadow-md mb-3 mx-auto" width="96" height="96" />
      <p class="font-semibold text-gray-900">Tina Pangan</p>
      <p class="text-gray-600 text-sm">Executive Assistant to the General Manager</p>
    </div>

    <!-- Rica -->
    <div class="w-[180px]">
      <img src="https://scontent.fmnl30-1.fna.fbcdn.net/v/t39.30808-1/473712286_3382590018543063_656822981838360944_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=106&ccb=1-7&_nc_sid=e99d92&_nc_eui2=AeEhL7MPsUoGo12_Ibhn6PE32fCab2W8Cm_Z8JpvZbwKb0936Etw91V7OZRFt801m2MsGTYTzUVNBwuhJ8qCNHOG&_nc_ohc=YhEgWaNnwK8Q7kNvwHj66y_&_nc_oc=AdmwfEcX6sKP8GWc7OEsR-8mHN-y9Gk_al2M06gpOIZuis1r_A_DV4tbsV33__StRa4&_nc_zt=24&_nc_ht=scontent.fmnl30-1.fna&_nc_gid=f2PNma-ZrzZS2EJvVlrDMg&oh=00_AfMEg1gsJApe1vOu3ZDtPQAPe8tqY7pNkznpMXZiPOMujw&oe=686A66E8" alt="Rica Joy Tolomia" class="rounded-full shadow-md mb-3 mx-auto" width="96" height="96" />
      <p class="font-semibold text-gray-900">Rica Joy Tolomia</p>
      <p class="text-gray-600 text-sm">TA/HR Specialist</p>
    </div>
  </div>

 <p class="max-w-2xl text-center text-gray-700 italic mt-12 mx-auto">
  Behind every line of code is a team that cared, a group of passionate individuals who brought their skills, insights, and time to turn an idea into a fully working system. From brainstorming sessions to testing, refining, and implementing features, this project is the result of true collaboration. Cedrick and RJ led the technical foundation with their commitment and late-night problem-solving, while Tina, Neil, and Rica offered constant support, feedback, and direction that helped shape the system to what it is today. 
  <br><br>
  Special thanks to RJ for pushing major updates that elevated the platform’s performance and usability. This system is more than a tool, it's a reflection of teamwork, learning, and shared purpose. We’re proud of what we’ve created together.
</p>

</section>

  </main>

  <script>
    // Mobile menu toggle
    const btn = document.getElementById('mobile-menu-button');
    const menu = document.getElementById('mobile-menu');
    btn.addEventListener('click', () => {
      menu.classList.toggle('hidden');
    });
  </script>
</body>
</html>

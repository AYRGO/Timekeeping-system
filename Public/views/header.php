<!-- header.php -->
<header class="bg-white shadow-md p-4 flex justify-between items-center w-full">
    <div class="flex items-center">
        <!-- Slide Toggle Button for mobile -->
        <button @click="open = !open" class="md:hidden mr-3 text-gray-600 hover:text-gray-800 focus:outline-none">
            <div class="relative w-12 h-6 bg-gray-300 rounded-full transition-colors duration-200" :class="{ 'bg-green-500': open }">
                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200" 
                     :class="{ 'translate-x-6': open }">
                </div>
            </div>
        </button>
        
        <h1 class="text-xl font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h1>
    </div>
    
    <!-- Back to Employee View Text -->
    <span onclick="switchToEmployeeView()" 
          class="cursor-pointer text-green-600 hover:text-green-700 font-medium text-xs md:text-sm transition duration-200">
        <svg class="inline w-3 h-3 md:w-4 md:h-4 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
        </svg>
        <span class="hidden sm:inline">Back to Employee View</span>
        <span class="sm:hidden">Back</span>
    </span>
</header>

<script>
function switchToEmployeeView() {
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin_homepage.php'; // Or current page
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'switch_to_employee';
    input.value = '1';
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>
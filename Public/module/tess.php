<?php
// HRIS Newsfeed View - Creative Design

// Simulate fetching newsfeed data from HRIS system
function getNewsfeedData() {
    return [
        [
            'id' => 1,
            'type' => 'announcement',
            'title' => 'Quarterly Company Meeting',
            'content' => 'Join us this Friday for our quarterly all-hands meeting in the main auditorium.',
            'date' => '2023-05-15',
            'author' => 'CEO Office',
            'priority' => 'high'
        ],
        [
            'id' => 2,
            'type' => 'policy',
            'title' => 'Updated WFH Policy',
            'content' => 'The work-from-home policy has been updated to reflect new hybrid work standards.',
            'date' => '2023-05-10',
            'author' => 'HR Department'
        ],
        [
            'id' => 3,
            'type' => 'birthday',
            'title' => 'Employee Birthdays',
            'content' => 'Celebrating birthday of Sarah Johnson (Development Dept) today!',
            'date' => '2023-05-08',
            'author' => 'HR Team'
        ],
        [
            'id' => 4,
            'type' => 'event',
            'title' => 'Tech Workshop',
            'content' => 'Sign up for the upcoming DevOps workshop on May 20th.',
            'date' => '2023-05-05',
            'author' => 'Learning & Development'
        ]
    ];
}

// Fetch motivational quote from API (using placeholder for demonstration)
function getMotivationalQuote() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.quotable.io/random");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Set false only for local dev testing
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // Optional: Log error
        curl_close($ch);
        return [
            'content' => 'Success is the sum of small efforts repeated day in and day out.',
            'author' => 'Robert Collier'
        ];
    }

    curl_close($ch);
    $data = json_decode($response, true);
    return [
        'content' => $data['content'] ?? 'Success is the sum of small efforts repeated day in and day out.',
        'author' => $data['author'] ?? 'Robert Collier'
    ];
}

// Fetch weather data (using placeholder for demonstration)
function getWeatherData() {
    return [
        'temperature' => rand(65, 85),
        'condition' => ['Sunny', 'Partly Cloudy', 'Rainy', 'Clear'][rand(0, 3)],
        'icon' => 'https://placehold.co/100x100/3A86FF/FFFFFF?text=' . urlencode('☀️')
    ];
}

$newsfeedItems = getNewsfeedData();
$quote = getMotivationalQuote();
$weather = getWeatherData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS Newsfeed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3A86FF;
            --secondary: #8338EC;
            --accent: #FF006E;
            --light: #FBFFFE;
            --dark: #1A1A2E;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
        }
        
        .newsfeed-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
        }
        
        .newsfeed-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .newsfeed-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .announcement-icon {
            background: var(--primary);
        }
        
        .policy-icon {
            background: var(--secondary);
        }
        
        .birthday-icon {
            background: var(--accent);
        }
        
        .event-icon {
            background: #06D6A0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .card-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            font-size: 0.85rem;
            color: #777;
        }
        
        .sidebar-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .weather-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .weather-temp {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .quote-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .quote-text {
            font-size: 1.1rem;
            font-style: italic;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .quote-author {
            font-weight: 600;
            position: relative;
            z-index: 2;
            text-align: right;
        }
        
        .quote-pattern {
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            opacity: 0.2;
            z-index: 1;
        }
        
        .priority-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 40px;
            height: 40px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(255,0,110,0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .emoji-reactions {
            display: flex;
            gap: 0.5rem;
        }
        
        .emoji-reaction {
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s ease;
        }
        
        .emoji-reaction:hover {
            transform: scale(1.2);
        }
        
        @media (max-width: 768px) {
            .newsfeed-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="newsfeed-container">
        <div class="main-content">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">HRIS Newsfeed</h1>
            
            <?php foreach ($newsfeedItems as $item): ?>
                <div class="newsfeed-card">
                    <?php if (isset($item['priority']) && $item['priority'] === 'high'): ?>
                        <div class="priority-badge">
                            <i class="fas fa-exclamation"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-header">
                        <?php 
                        $icon = '';
                        $iconClass = '';
                        switch ($item['type']) {
                            case 'announcement':
                                $icon = '<i class="fas fa-bullhorn"></i>';
                                $iconClass = 'announcement-icon';
                                break;
                            case 'policy':
                                $icon = '<i class="fas fa-file-alt"></i>';
                                $iconClass = 'policy-icon';
                                break;
                            case 'birthday':
                                $icon = '<i class="fas fa-birthday-cake"></i>';
                                $iconClass = 'birthday-icon';
                                break;
                            case 'event':
                                $icon = '<i class="fas fa-calendar-alt"></i>';
                                $iconClass = 'event-icon';
                                break;
                        }
                        ?>
                        <div class="card-icon <?php echo $iconClass; ?>">
                            <?php echo $icon; ?>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500"><?php echo ucfirst($item['type']); ?></div>
                            <div class="text-xs text-gray-400">Posted on <?php echo date('M j, Y', strtotime($item['date'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h2 class="card-title"><?php echo $item['title']; ?></h2>
                        <p class="card-content"><?php echo $item['content']; ?></p>
                        
                        <?php if ($item['type'] === 'birthday'): ?>
                            <div class="mt-4 flex items-center gap-3">
                                <img src="https://placehold.co/80x80/FF006E/FFFFFF?text=🎂" alt="Birthday cake decoration" class="rounded-full">
                                <div>
                                    <div class="font-medium">Send birthday wishes!</div>
                                    <div class="text-sm">Click to post a greeting on the employee wall</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <div class="text-sm">
                            <i class="fas fa-user-circle mr-1"></i> <?php echo $item['author']; ?>
                        </div>
                        <div class="emoji-reactions">
                            <span class="emoji-reaction" onclick="react(<?php echo $item['id']; ?>, 'like')"><i class="far fa-thumbs-up"></i></span>
                            <span class="emoji-reaction" onclick="react(<?php echo $item['id']; ?>, 'celebrate')"><i class="far fa-grin-stars"></i></span>
                            <span class="emoji-reaction" onclick="react(<?php echo $item['id']; ?>, 'comment')"><i class="far fa-comment-dots"></i></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="sidebar">
            <div class="sidebar-card">
                <h2 class="sidebar-title">
                    <i class="fas fa-sun text-yellow-500"></i>
                    Office Weather
                </h2>
                <div class="weather-display">
                    <div>
                        <div class="weather-temp"><?php echo $weather['temperature']; ?>°F</div>
                        <div class="text-gray-600"><?php echo $weather['condition']; ?></div>
                        <div class="text-sm text-gray-500">Headquarters Weather</div>
                    </div>
                    <img src="<?php echo $weather['icon']; ?>" alt="<?php echo $weather['condition']; ?> weather icon" width="80">
                </div>
                <div class="text-xs text-gray-500">
                    Weather data updated hourly. Dress appropriately for office conditions.
                </div>
            </div>
            
            <div class="quote-card">
                <svg class="quote-pattern" width="100" height="100" viewBox="0 0 100 100">
                    <path d="M0 0 L100 0 L100 100 Z" fill="white"></path>
                </svg>
                <p class="quote-text">"<?php echo $quote['content']; ?>"</p>
                <p class="quote-author">— <?php echo $quote['author']; ?></p>
            </div>
            
            <div class="sidebar-card">
                <h2 class="sidebar-title">
                    <i class="fas fa-bolt text-purple-500"></i>
                    Quick Actions
                </h2>
                <div class="space-y-3">
                    <button class="flex items-center gap-2 w-full p-3 rounded-lg hover:bg-purple-50 transition">
                        <i class="fas fa-file-signature text-purple-600"></i>
                        Submit Timesheet
                    </button>
                    <button class="flex items-center gap-2 w-full p-3 rounded-lg hover:bg-purple-50 transition">
                        <i class="fas fa-calendar-check text-purple-600"></i>
                        Request Time Off
                    </button>
                    <button class="flex items-center gap-2 w-full p-3 rounded-lg hover:bg-purple-50 transition">
                        <i class="fas fa-question-circle text-purple-600"></i>
                        Open HR Ticket
                    </button>
                </div>
            </div>
            
            <div class="sidebar-card">
                <h2 class="sidebar-title">
                    <i class="fas fa-birthday-cake text-pink-500"></i>
                    Upcoming Birthdays
                </h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <img src="https://placehold.co/40x40/3A86FF/FFFFFF?text=JD" alt="Employee profile John Doe" class="rounded-full">
                        <div>
                            <div class="font-medium">John Doe</div>
                            <div class="text-xs text-gray-500">May 18</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <img src="https://placehold.co/40x40/06D6A0/FFFFFF?text=AS" alt="Employee profile Amy Smith" class="rounded-full">
                        <div>
                            <div class="font-medium">Amy Smith</div>
                            <div class="text-xs text-gray-500">May 22</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function react(postId, reaction) {
            // In a real implementation, this would send an AJAX request
            console.log(`Reacted with ${reaction} to post ${postId}`);
            
            // Visual feedback
            const reactions = {
                'like': { icon: 'fas fa-thumbs-up', color: 'text-blue-500' },
                'celebrate': { icon: 'fas fa-grin-stars', color: 'text-yellow-500' },
                'comment': { icon: 'fas fa-comment-dots', color: 'text-green-500' }
            };
            
            // Find the reaction button and update it
            const buttons = document.querySelectorAll(`[onclick="react(${postId}, '${reaction}')"]`);
            buttons.forEach(button => {
                button.innerHTML = `<i class="${reactions[reaction].icon} ${reactions[reaction].color}"></i>`;
                button.onclick = null;
                
                // Add animation
                button.style.animation = 'bounce 0.5s';
                setTimeout(() => {
                    button.style.animation = '';
                }, 500);
            });
            
            // Toast notification
            showToast(`You reacted with ${reaction}`);
        }
        
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg transform transition duration-300 opacity-0 translate-y-4';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('opacity-100');
                toast.classList.remove('translate-y-4');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('opacity-100');
                toast.classList.add('translate-y-4');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
        
        // Set up CSS for toast animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.3); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

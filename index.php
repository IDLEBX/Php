
<?php
// ============== الإعدادات الأساسية ==============
date_default_timezone_set('Asia/Riyadh');
set_time_limit(0);
error_reporting(0);
ob_start();

// ============== معلومات البوت ==============
$token = '8930886146:AAHskVBFtIDy4No5LhWD2x4T3-XD4m-KVVY';
define('API_KEY', $token);

$admin = "7240148750";
$adminuz = "cyber_idleb";
$sudo = array("cyber_idleb", $admin);
$proof_channel = "idlebx2";

// ============== إنشاء الملفات المطلوبة ==============
function createRequiredFiles() {
    $files = [
        'sales.json' => ['sales' => [], 'users' => [], 'stats' => ['total_users' => 0, 'total_sales' => 0, 'total_points' => 0]],
        'products.json' => ['products' => []],
        'users.json' => ['users' => []],
        'settings.json' => ['bot_name' => 'متجر Cyber idleb', 'welcome_message' => '🎉 مرحباً بك'],
        'channels.json' => ['channels' => []],
        'admin.json' => ['admins' => ["7615821064"], 'banned' => []],
        'proofs.json' => ['proofs' => []]
    ];
    
    foreach ($files as $filename => $default_data) {
        if (!file_exists($filename)) {
            file_put_contents($filename, json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    
    for ($i = 0; $i <= 2; $i++) {
        $file = "channel{$i}.txt";
        if (!file_exists($file)) {
            file_put_contents($file, "");
        }
    }
}
createRequiredFiles();

// ============== إضافة القنوات الإجبارية المطلوبة ==============
function addRequiredChannels() {
    $channels = getChannels();
    
    // القنوات المطلوبة بالضبط
    $required_channels = [
        [
            'name' => 'قناة المتجر',
            'username' => 'cyber_idleb',
            'added_by' => 'system',
            'added_date' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'قناة الحسابات',
            'username' => 'idlebx2',
            'added_by' => 'system',
            'added_date' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'قناة الإثباتات',
            'username' => 'Cyber_proof_of_Idlib',
            'added_by' => 'system',
            'added_date' => date('Y-m-d H:i:s')
        ]
    ];
    
    // إذا كانت القنوات مختلفة، نقوم بتحديثها
    $channels_to_save = $required_channels;
    saveChannels($channels_to_save);
}
addRequiredChannels();

// ============== دالة الاتصال بالتيليجرام ==============
function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL Error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($res, true);
}

// ============== دوال حفظ وتحميل البيانات ==============
function saveData($filename, $data) {
    return file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadData($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    $content = file_get_contents($filename);
    if (empty($content)) {
        return [];
    }
    $data = json_decode($content, true);
    return ($data === null) ? [] : $data;
}

// ============== إدارة المستخدمين ==============
function addNewUser($user_id, $username, $name) {
    global $sudo;
    
    $users_data = loadData('users.json');
    $sales_data = loadData('sales.json');
    
    $is_new = false;
    
    if (!isset($users_data[$user_id])) {
        $is_new = true;
        
        $users_data[$user_id] = [
            'username' => $username ?: 'بدون معرف',
            'name' => $name ?: 'بدون اسم',
            'join_date' => date('Y-m-d H:i:s'),
            'last_seen' => date('Y-m-d H:i:s'),
            'points' => 10,
            'referral_link' => "https://t.me/" . bot('getMe')['result']['username'] . "?start={$user_id}"
        ];
        
        $sales_data['stats']['total_users'] = ($sales_data['stats']['total_users'] ?? 0) + 1;
        saveData('sales.json', $sales_data);
        
        saveData('users.json', $users_data);
        
        $total_users = $sales_data['stats']['total_users'] ?? 1;
        
        $admin_message = "____________________\n";
        $admin_message .= "دخل شخص جديد للبوت ☁\n";
        $admin_message .= "يوزر 📠 : @" . ($username ?: 'بدون معرف') . "\n";
        $admin_message .= "ايديه 🆔 : `{$user_id}`\n";
        $admin_message .= "عدد المشتركين : {$total_users}\n";
        $admin_message .= "____________________";
        
        foreach ($sudo as $admin_id) {
            bot('sendMessage', [
                'chat_id' => $admin_id,
                'text' => $admin_message,
                'parse_mode' => 'Markdown'
            ]);
        }
        
    } else {
        $users_data[$user_id]['last_seen'] = date('Y-m-d H:i:s');
        if ($username) $users_data[$user_id]['username'] = $username;
        if ($name) $users_data[$user_id]['name'] = $name;
        saveData('users.json', $users_data);
    }
    
    return $is_new;
}

// ============== نظام القنوات الإجبارية ==============
function getChannels() {
    $channels_data = loadData('channels.json');
    return $channels_data['channels'] ?? [];
}

function saveChannels($channels) {
    $channels_data = ['channels' => $channels];
    return saveData('channels.json', $channels_data);
}

function checkChannelSubscription($user_id) {
    global $sudo;
    
    // المشرفين لا يحتاجون للاشتراك
    if (in_array($user_id, $sudo)) {
        return true;
    }
    
    $channels = getChannels();
    
    if (empty($channels)) {
        return true;
    }
    
    foreach ($channels as $channel) {
        if (empty($channel['username'])) {
            continue;
        }
        
        $channel_username = str_replace('@', '', $channel['username']);
        
        $result = bot('getChatMember', [
            'chat_id' => '@' . $channel_username,
            'user_id' => $user_id
        ]);
        
        if (!isset($result['ok']) || !$result['ok']) {
            return false;
        }
        
        $status = $result['result']['status'] ?? '';
        
        if (!in_array($status, ['member', 'administrator', 'creator'])) {
            return false;
        }
    }
    
    return true;
}

function showChannelsMenu($chat_id, $message_id = null) {
    $channels = getChannels();
    $keyboard = [];
    
    foreach ($channels as $channel) {
        if (!empty($channel['username'])) {
            $channel_username = str_replace('@', '', $channel['username']);
            $keyboard[] = [[
                'text' => "📢 اشترك في قناة {$channel['name']}",
                'url' => "https://t.me/{$channel_username}"
            ]];
        }
    }
    
    $keyboard[] = [[
        'text' => '✅ تحقق من الاشتراك',
        'callback_data' => 'check_subscription'
    ]];
    
    $text = "⚠️ *عذراً، لا يمكنك استخدام البوت* ⚠️\n\n";
    $text .= "📢 *للاستمرار، يجب عليك الاشتراك في القنوات التالية:*\n\n";
    
    $channel_list = [
        ['name' => 'قناة المتجر', 'username' => 'cyber_idleb'],
        ['name' => 'قناة الحسابات', 'username' => 'idlebx2'],
        ['name' => 'قناة الإثباتات', 'username' => 'Cyber_proof_of_Idlib']
    ];
    
    foreach ($channel_list as $index => $channel) {
        $num = $index + 1;
        $text .= "{$num}. [@{$channel['username']}](https://t.me/{$channel['username']}) - {$channel['name']}\n";
    }
    
    $text .= "\n✅ *بعد الاشتراك، اضغط على زر التحقق*";
    
    if ($message_id) {
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    } else {
        return bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
}

// ============== نظام النقاط ==============
function getUserPoints($user_id) {
    $users_data = loadData('users.json');
    return $users_data[$user_id]['points'] ?? 0;
}

function updateUserPoints($user_id, $points, $operation = 'add', $admin_id = null, $transfer = false) {
    $users_data = loadData('users.json');
    
    if (!isset($users_data[$user_id])) {
        $users_data[$user_id] = [
            'username' => 'بدون معرف',
            'name' => 'بدون اسم',
            'join_date' => date('Y-m-d H:i:s'),
            'points' => 0
        ];
    }
    
    $current = $users_data[$user_id]['points'] ?? 0;
    
    switch ($operation) {
        case 'add':
            $new = $current + $points;
            break;
        case 'subtract':
            $new = $current - $points;
            if ($new < 0) $new = 0;
            break;
        case 'set':
            $new = max(0, $points);
            break;
        default:
            $new = $current;
    }
    
    $users_data[$user_id]['points'] = $new;
    saveData('users.json', $users_data);
    
    if ($operation == 'add') {
        $sales_data = loadData('sales.json');
        $sales_data['stats']['total_points'] = ($sales_data['stats']['total_points'] ?? 0) + $points;
        saveData('sales.json', $sales_data);
    }
    
    if ($admin_id && $operation == 'add' && $transfer) {
        $admin_info = getUserInfo($admin_id);
        $user_info = getUserInfo($user_id);
        
        $message = "🎁 *مبروك! تم إرسال نقاط لك*\n\n";
        $message .= "💰 *المبلغ:* {$points} نقطة\n";
        $message .= "💎 *نقاطك الحالية:* {$new} نقطة\n";
        $message .= "👨‍💼 *من قبل:* @{$admin_info['username']}\n";
        $message .= "📅 *التاريخ:* " . date('Y-m-d H:i:s');
        
        bot('sendMessage', [
            'chat_id' => $user_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }
    
    return $new;
}

// ============== معلومات المستخدم ==============
function getUserInfo($user_id) {
    $users_data = loadData('users.json');
    $user_data = $users_data[$user_id] ?? [
        'username' => 'بدون معرف',
        'name' => 'بدون اسم',
        'join_date' => date('Y-m-d H:i:s'),
        'points' => 0
    ];
    
    $sales_data = loadData('sales.json');
    $purchases = 0;
    if (isset($sales_data[$user_id]['purchases'])) {
        $purchases = count($sales_data[$user_id]['purchases']);
    }
    
    $referrals = 0;
    if (isset($sales_data[$user_id]['referrals'])) {
        $referrals = count($sales_data[$user_id]['referrals']);
    }
    
    $used_points = 0;
    if (isset($sales_data[$user_id]['purchases'])) {
        foreach ($sales_data[$user_id]['purchases'] as $purchase) {
            $used_points += $purchase['price'];
        }
    }
    
    return [
        'points' => $user_data['points'],
        'referrals' => $referrals,
        'purchases' => $purchases,
        'used_points' => $used_points,
        'join_date' => $user_data['join_date'],
        'username' => $user_data['username'],
        'name' => $user_data['name']
    ];
}

// ============== إحصائيات البوت ==============
function getBotStats() {
    $sales_data = loadData('sales.json');
    $products_data = loadData('products.json');
    $users_data = loadData('users.json');
    
    $total_users = count($users_data);
    $total_sales = $sales_data['stats']['total_sales'] ?? 0;
    $total_points = $sales_data['stats']['total_points'] ?? 0;
    $total_products = count($products_data['products'] ?? []);
    
    $distributed_points = 0;
    foreach ($users_data as $user) {
        $distributed_points += $user['points'] ?? 0;
    }
    
    $used_points = 0;
    foreach ($sales_data as $user_id => $data) {
        if ($user_id !== 'stats' && isset($data['purchases'])) {
            foreach ($data['purchases'] as $purchase) {
                $used_points += $purchase['price'];
            }
        }
    }
    
    $active_users = 0;
    $seven_days_ago = strtotime('-7 days');
    foreach ($users_data as $user) {
        if (isset($user['last_seen'])) {
            $last_seen = strtotime($user['last_seen']);
            if ($last_seen >= $seven_days_ago) {
                $active_users++;
            }
        }
    }
    
    $avg_points = $total_users > 0 ? round($distributed_points / $total_users, 2) : 0;
    
    $today_sales = 0;
    $today = date('Y-m-d');
    foreach ($sales_data as $user_id => $data) {
        if ($user_id !== 'stats' && isset($data['purchases'])) {
            foreach ($data['purchases'] as $purchase) {
                if (date('Y-m-d', strtotime($purchase['purchase_date'])) == $today) {
                    $today_sales++;
                }
            }
        }
    }
    
    return [
        'total_users' => $total_users,
        'total_sales' => $total_sales,
        'total_points' => $total_points,
        'total_products' => $total_products,
        'distributed_points' => $distributed_points,
        'used_points' => $used_points,
        'active_users' => $active_users,
        'avg_points' => $avg_points,
        'today_sales' => $today_sales
    ];
}

// ============== القائمة الرئيسية (معدلة حسب الطلب) ==============
function showMainMenu($chat_id, $message_id = null, $is_new_message = false) {
    global $sudo;
    
    $stats = getBotStats();
    $user_info = getUserInfo($chat_id);
    
    $text = "```  𝒀𝒐𝒖 𝒂𝒓𝒆 𝒂 𝒖𝒔𝒆𝒓 𝐕𝐢𝐏 💳 ```\n";
    $text .= "𓆩💀 *مرحباً بك في متجر Cyber Idleb* 💀 𓆪\n\n";
    $text .= "✨ أفضل متجر للسلع الرقمية بأفضل الأسعار\n\n";
    $text .= "📊 *إحصائيات عامة:*\n";
    $text .= "👥 عدد المستخدمين: " . $stats['total_users'] . "\n";
    $text .= "💰 نقاطك الحالية: *" . $user_info['points'] . "* نقطة\n\n";
    $text .= "👇 *اختر من القائمة:*";
    
    $keyboard = [
        [
            ['text' => '⌯ 📟 تصفح المتجر ⌯', 'callback_data' => 'grid_page_1'],
            ['text' => '⌯ 💰 تجميع النقاط ⌯', 'callback_data' => 'collect_points']
        ],
        [
            ['text' => '⌯ 📊 إحصائياتي ⌯', 'callback_data' => 'my_stats'],
            ['text' => '⌯ 🎁 الهدية اليومية ⌯', 'callback_data' => 'daily_gift']
        ],
        [
            ['text' => '⌯ 🛍️ مشترياتي ⌯', 'callback_data' => 'my_purchases'],
            ['text' => '⌯ 📞 تواصل معنا ⌯', 'callback_data' => 'contact_us']
        ],
        [
            ['text' => '⌯ 💸 تحويل نقاط ⌯', 'callback_data' => 'user_transfer_points'],
            ['text' => '⌯ 🔍 بحث عن منتج ⌯', 'callback_data' => 'search_product']
        ],
        [
            ['text' => '📢 قناة الإثباتات', 'url' => 'https://t.me/Cyber_proof_of_Idlib']
        ]
    ];
    
    if (in_array($chat_id, $sudo)) {
        $keyboard[] = [['text' => '⌯ ⚙️ لوحة التحكم ⌯', 'callback_data' => 'admin_panel']];
    }
    
    $reply_markup = ['inline_keyboard' => $keyboard];
    
    if ($is_new_message) {
        return bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($reply_markup)
        ]);
    } elseif ($message_id) {
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($reply_markup)
        ]);
    }
}

// ============== نظام تحويل النقاط للمستخدمين ==============
function startUserTransferPoints($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['user_transfer_mode'] = [
        'user_id' => $chat_id,
        'step' => 'transfer_to_user',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "💸 *تحويل نقاط*\n\n";
    $text .= "أرسل أيدي المستخدم الذي تريد تحويل النقاط إليه:";
    
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'main_menu']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function processUserTransferPoints($chat_id, $text) {
    $admin_data = loadData('admin.json');
    $transfer_mode = $admin_data['user_transfer_mode'] ?? null;
    
    if (!$transfer_mode || $transfer_mode['user_id'] != $chat_id) {
        return false;
    }
    
    $step = $transfer_mode['step'];
    
    if ($step == 'transfer_to_user') {
        if (!is_numeric($text)) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ أيدي غير صحيح! أرسل أيدي المستخدم (رقم فقط):",
                'parse_mode' => 'Markdown'
            ]);
            return true;
        }
        
        $to_user_id = (int)$text;
        
        $users_data = loadData('users.json');
        if (!isset($users_data[$to_user_id])) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ المستخدم غير موجود في قاعدة البيانات!",
                'parse_mode' => 'Markdown'
            ]);
            return true;
        }
        
        $admin_data['user_transfer_temp'] = ['to_user_id' => $to_user_id];
        $admin_data['user_transfer_mode']['step'] = 'transfer_amount';
        saveData('admin.json', $admin_data);
        
        $user_info = getUserInfo($to_user_id);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم العثور على المستخدم*\n\n👤 المستلم: @{$user_info['username']}\n🆔 الأيدي: {$to_user_id}\n\nأرسل عدد النقاط للتحويل:",
            'parse_mode' => 'Markdown'
        ]);
        return true;
    }
    elseif ($step == 'transfer_amount') {
        if (!is_numeric($text) || $text <= 0) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ عدد غير صحيح! أرسل عدد النقاط (رقم أكبر من الصفر):",
                'parse_mode' => 'Markdown'
            ]);
            return true;
        }
        
        $from_user_id = $chat_id;
        $to_user_id = $admin_data['user_transfer_temp']['to_user_id'];
        $amount = (int)$text;
        
        $from_points = getUserPoints($from_user_id);
        
        if ($from_points < $amount) {
            $message = "❌ *فشل التحويل*\n\n";
            $message .= "👤 المرسل: أنت\n";
            $message .= "💎 رصيدك الحالي: {$from_points} نقطة\n";
            $message .= "💰 المبلغ المطلوب: {$amount} نقطة\n\n";
            $message .= "رصيدك غير كافي للتحويل!";
        } else {
            $new_from_points = updateUserPoints($from_user_id, $amount, 'subtract');
            $new_to_points = updateUserPoints($to_user_id, $amount, 'add', $from_user_id, true);
            
            $message = "✅ *تم التحويل بنجاح*\n\n";
            $message .= "👤 المرسل: أنت\n";
            $message .= "👤 المستلم: {$to_user_id}\n";
            $message .= "💰 المبلغ المحول: {$amount} نقطة\n";
            $message .= "💎 رصيدك الجديد: {$new_from_points} نقطة\n\n";
            $message .= "📢 *تم إشعار المستلم بالتحويل*";
            
            $from_info = getUserInfo($from_user_id);
            bot('sendMessage', [
                'chat_id' => $to_user_id,
                'text' => "💰 *تم استلام نقاط*\n\n👤 من: @{$from_info['username']}\n💰 المبلغ: {$amount} نقطة\n💎 رصيدك الجديد: {$new_to_points} نقطة",
                'parse_mode' => 'Markdown'
            ]);
        }
        
        unset($admin_data['user_transfer_mode']);
        unset($admin_data['user_transfer_temp']);
        saveData('admin.json', $admin_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => [
                [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
            ]])
        ]);
        return true;
    }
    
    return false;
}

// ============== نظام المتجر ==============
function prepareProductGrid($page = 1) {
    $products_data = loadData('products.json');
    $all_products = $products_data['products'] ?? [];
    
    if (empty($all_products)) {
        return [
            'products' => [],
            'total_pages' => 0,
            'current_page' => 1,
            'total_products' => 0
        ];
    }
    
    $products_per_page = 6;
    $total_products = count($all_products);
    $total_pages = ceil($total_products / $products_per_page);
    $page = max(1, min($page, $total_pages));
    $start_index = ($page - 1) * $products_per_page;
    $current_products = array_slice($all_products, $start_index, $products_per_page);
    
    $grid_products = [];
    for ($i = 0; $i < count($current_products); $i += 2) {
        $row = [];
        if (isset($current_products[$i])) {
            $row[] = $current_products[$i];
        }
        if (isset($current_products[$i + 1])) {
            $row[] = $current_products[$i + 1];
        }
        if (!empty($row)) {
            $grid_products[] = $row;
        }
    }
    
    return [
        'products' => $grid_products,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_products' => $total_products
    ];
}

function showGridPage($chat_id, $message_id, $page = 1) {
    $grid_data = prepareProductGrid($page);
    
    if (empty($grid_data['products'])) {
        $text = "🕸🕷 *المتجر فارغ حالياً*\n\nلا توجد منتجات متاحة للعرض حالياً.";
        $keyboard = [[['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $text = "🛍️ *تصفح المتجر*\n\n";
    $text .= "📋 الصفحة: {$grid_data['current_page']}/{$grid_data['total_pages']}\n";
    $text .= "📊 عدد المنتجات: {$grid_data['total_products']}\n\n";
    $text .= "👇 *اختر المنتج الذي تريده:*";
    
    $keyboard = [];
    
    foreach ($grid_data['products'] as $row) {
        $keyboard_row = [];
        foreach ($row as $product) {
            $display_text = $product['emoji'] . " " . $product['name'];
            $keyboard_row[] = ['text' => $display_text, 'callback_data' => 'product_' . $product['code']];
        }
        $keyboard[] = $keyboard_row;
    }
    
    $nav_row = [];
    if ($grid_data['current_page'] > 1) {
        $nav_row[] = ['text' => '◀️ السابقة', 'callback_data' => 'grid_page_' . ($grid_data['current_page'] - 1)];
    }
    if ($grid_data['current_page'] < $grid_data['total_pages']) {
        $nav_row[] = ['text' => 'التالية ▶️', 'callback_data' => 'grid_page_' . ($grid_data['current_page'] + 1)];
    }
    if (!empty($nav_row)) {
        $keyboard[] = $nav_row;
    }
    
    $keyboard[] = [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function showProductDetails($chat_id, $message_id, $product_code) {
    $products_data = loadData('products.json');
    
    $product = null;
    foreach ($products_data['products'] as $p) {
        if ($p['code'] == $product_code) {
            $product = $p;
            break;
        }
    }
    
    if (!$product) {
        $text = "❌ *المنتج غير موجود*\n\nتم حذف هذا المنتج أو أنه غير متوفر حالياً.";
        $keyboard = [[['text' => '🔙 العودة للمتجر', 'callback_data' => 'grid_page_1']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $user_points = getUserPoints($chat_id);
    $can_purchase = ($user_points >= $product['price']);
    
    $text = "📦 *تفاصيل المنتج*\n\n";
    $text .= "🏷️ *الاسم:* {$product['name']}\n";
    $text .= "💰 *السعر:* {$product['price']} نقطة\n";
    $text .= "📁 *النوع:* {$product['file_type']}\n";
    $text .= "📝 *الوصف:* " . ($product['description'] ?? 'لا يوجد وصف') . "\n";
    $text .= "🆔 *الكود:* `{$product_code}`\n\n";
    $text .= "💎 *نقاطك الحالية:* {$user_points} نقطة\n\n";
    
    if ($can_purchase) {
        $text .= "✅ *يمكنك شراء هذا المنتج*";
    } else {
        $needed = $product['price'] - $user_points;
        $text .= "❌ *نقاطك غير كافية*\nتحتاج إلى {$needed} نقطة إضافية";
    }
    
    $keyboard = [];
    if ($can_purchase) {
        $keyboard[] = [['text' => '🛒 شراء الآن', 'callback_data' => 'buy_now_' . $product_code]];
    } else {
        $keyboard[] = [['text' => '💰 تجميع النقاط', 'callback_data' => 'collect_points']];
    }
    
    $keyboard[] = [
        ['text' => '🔙 العودة للمتجر', 'callback_data' => 'grid_page_1'],
        ['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== نظام الإثباتات التلقائية ==============
function autoProofSystem($chat_id, $product, $purchase_id) {
    global $proof_channel, $admin;
    
    $users_data = loadData('users.json');
    $user_info = $users_data[$chat_id] ?? [];
    $username = $user_info['username'] ?? 'بدون معرف';
    
    $proof_message = "🛒 *تمت عملية شراء جديدة*\n\n";
    $proof_message .= "🆔 *رقم الطلب:* `{$purchase_id}`\n";
    $proof_message .= "👤 المشتري: @{$username}\n";
    $proof_message .= "🆔 الأيدي: `{$chat_id}`\n";
    $proof_message .= "📦 المنتج: {$product['name']}\n";
    $proof_message .= "💰 السعر: {$product['price']} نقطة\n";
    $proof_message .= "📅 التاريخ: " . date('Y-m-d H:i:s') . "\n";
    $proof_message .= "✅ *تم التسليم تلقائياً*";
    
    $channel_result = bot('sendMessage', [
        'chat_id' => $proof_channel,
        'text' => $proof_message,
        'parse_mode' => 'Markdown'
    ]);
    
    $proofs_data = loadData('proofs.json');
    $proofs_data['proofs'][] = [
        'purchase_id' => $purchase_id,
        'user_id' => $chat_id,
        'username' => $username,
        'product_name' => $product['name'],
        'price' => $product['price'],
        'purchase_date' => date('Y-m-d H:i:s'),
        'status' => 'completed',
        'proof_channel_message_id' => $channel_result['ok'] ? $channel_result['result']['message_id'] : null,
        'auto_proof' => true,
        'proof_date' => date('Y-m-d H:i:s')
    ];
    saveData('proofs.json', $proofs_data);
    
    return $channel_result['ok'];
}

function processPurchase($chat_id, $message_id, $product_code) {
    global $admin;
    
    $products_data = loadData('products.json');
    $users_data = loadData('users.json');
    
    $product = null;
    foreach ($products_data['products'] as $p) {
        if ($p['code'] == $product_code) {
            $product = $p;
            break;
        }
    }
    
    if (!$product) {
        return ['error' => ' ❎ المنتج غير موجود'];
    }
    
    $user_points = getUserPoints($chat_id);
    
    if ($user_points < $product['price']) {
        return ['error' => ' 🕸🕷 نقاطك غير كافية'];
    }
    
    $new_points = updateUserPoints($chat_id, $product['price'], 'subtract');
    
    $sales_data = loadData('sales.json');
    if (!isset($sales_data[$chat_id])) {
        $sales_data[$chat_id] = ['purchases' => [], 'referrals' => []];
    }
    
    $purchase_id = 'PUR' . strtoupper(substr(md5(uniqid()), 0, 8));
    $purchase_date = date('Y-m-d H:i:s');
    
    $sales_data[$chat_id]['purchases'][] = [
        'purchase_id' => $purchase_id,
        'product_code' => $product_code,
        'product_name' => $product['name'],
        'price' => $product['price'],
        'purchase_date' => $purchase_date,
        'status' => 'completed',
        'auto_proof' => true
    ];
    
    $bonus_points = floor($product['price'] * 0.1);
    if ($bonus_points < 1) $bonus_points = 1;
    $new_points = updateUserPoints($chat_id, $bonus_points, 'add');
    
    $sales_data['stats']['total_sales'] = ($sales_data['stats']['total_sales'] ?? 0) + 1;
    saveData('sales.json', $sales_data);
    
    $proof_sent = autoProofSystem($chat_id, $product, $purchase_id);
    
    $caption = "✅ *تم الشراء بنجاح*\n\n";
    $caption .= "🏷️ *المنتج:* {$product['name']}\n";
    $caption .= "💰 *السعر:* {$product['price']} نقطة\n";
    $caption .= "🎁 *مكافأة الشراء:* {$bonus_points} نقطة\n";
    $caption .= "💎 *نقاطك المتبقية:* {$new_points} نقطة\n";
    $caption .= "📅 *تاريخ الشراء:* {$purchase_date}\n";
    $caption .= "🆔 *رقم الطلب:* `{$purchase_id}`\n\n";
    
    if ($proof_sent) {
        $caption .= "📢 *تم رفع إثبات الشراء تلقائياً في قناة الإثباتات*\n";
    }
    
    $caption .= "شكراً لشرائك من متجرنا 🫶🏻🎒";
    
    $file_type = $product['file_type'] ?? 'document';
    $file_id = $product['file_id'] ?? '';
    $file_url = $product['file_url'] ?? '';
    
    if (!empty($file_id)) {
        switch ($file_type) {
            case 'photo':
                bot('sendPhoto', [
                    'chat_id' => $chat_id,
                    'photo' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
            case 'document':
                bot('sendDocument', [
                    'chat_id' => $chat_id,
                    'document' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
            case 'audio':
                bot('sendAudio', [
                    'chat_id' => $chat_id,
                    'audio' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
            case 'video':
                bot('sendVideo', [
                    'chat_id' => $chat_id,
                    'video' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
            case 'voice':
                bot('sendVoice', [
                    'chat_id' => $chat_id,
                    'voice' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
            case 'video_note':
                bot('sendVideoNote', [
                    'chat_id' => $chat_id,
                    'video_note' => $file_id,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
            default:
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                break;
        }
    } elseif (!empty($file_url)) {
        $caption .= "\n\n🔗 رابط التحميل: " . $file_url;
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $caption,
            'parse_mode' => 'Markdown'
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $caption,
            'parse_mode' => 'Markdown'
        ]);
    }
    
    $text = "✅ *تمت عملية الشراء بنجاح*\n\n";
    $text .= "تم إرسال المنتج إليك مباشرة!\n";
    $text .= "🎁 حصلت على {$bonus_points} نقطة مكافأة.\n\n";
    
    if ($proof_sent) {
        $text .= "📢 *تم رفع إثبات الشراء تلقائياً*";
    }
    
    $keyboard = [
        [['text' => '⌯ 📟 مواصلة التسوق ⌯', 'callback_data' => 'grid_page_1']],
        [['text' => '⌯ 🏠 الرئيسية ⌯', 'callback_data' => 'main_menu']]
    ];
    
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    $username = $users_data[$chat_id]['username'] ?? 'بدون معرف';
    
    $admin_message = "🛒 *طلب شراء جديد*\n\n";
    $admin_message .= "👤 المشتري: @{$username}\n";
    $admin_message .= "🆔 الأيدي: {$chat_id}\n";
    $admin_message .= "📦 المنتج: {$product['name']}\n";
    $admin_message .= "💰 السعر: {$product['price']} نقطة\n";
    $admin_message .= "🆔 كود المنتج: {$product_code}\n";
    $admin_message .= "🆔 رقم الطلب: {$purchase_id}\n";
    $admin_message .= "🎁 المكافأة: {$bonus_points} نقطة\n";
    $admin_message .= "📅 التاريخ: {$purchase_date}\n";
    $admin_message .= "✅ *الإثبات التلقائي:* " . ($proof_sent ? 'تم بنجاح' : 'فشل');
    
    bot('sendMessage', [
        'chat_id' => $admin,
        'text' => $admin_message,
        'parse_mode' => 'Markdown'
    ]);
    
    return ['success' => true, 'purchase_id' => $purchase_id];
}

// ============== نظام البحث عن المنتجات ==============
function showProductSearch($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['search_mode'] = [
        'user_id' => $chat_id,
        'step' => 'search_product',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "🔍 *بحث عن منتج*\n\n";
    $text .= "أرسل كود المنتج الذي تريد البحث عنه:\n\n";
    $text .= "📌 *ملاحظة:* كود المنتج يكون عادة بصيغة: `PDXXXXXXXX`";
    
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'main_menu']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function searchProductByCode($chat_id, $product_code) {
    $products_data = loadData('products.json');
    $found_product = null;
    
    foreach ($products_data['products'] as $product) {
        if ($product['code'] == $product_code) {
            $found_product = $product;
            break;
        }
    }
    
    $admin_data = loadData('admin.json');
    unset($admin_data['search_mode']);
    saveData('admin.json', $admin_data);
    
    if (!$found_product) {
        $text = "❌ *لم يتم العثور على المنتج*\n\n";
        $text .= "الكود الذي بحثت عنه: `{$product_code}`\n\n";
        $text .= "تأكد من كتابة الكود بشكل صحيح أو جرب البحث عن منتج آخر.";
        
        $keyboard = [
            [['text' => '🔍 بحث مرة أخرى', 'callback_data' => 'search_product']],
            [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
        ];
        
        return bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $user_points = getUserPoints($chat_id);
    $can_purchase = ($user_points >= $found_product['price']);
    
    $text = "🔍 *نتيجة البحث*\n\n";
    $text .= "✅ *تم العثور على المنتج*\n\n";
    $text .= "📦 *تفاصيل المنتج:*\n";
    $text .= "🏷️ *الاسم:* {$found_product['name']}\n";
    $text .= "💰 *السعر:* {$found_product['price']} نقطة\n";
    $text .= "📁 *النوع:* {$found_product['file_type']}\n";
    $text .= "📝 *الوصف:* " . ($found_product['description'] ?? 'لا يوجد وصف') . "\n";
    $text .= "🆔 *الكود:* `{$found_product['code']}`\n\n";
    $text .= "💎 *نقاطك الحالية:* {$user_points} نقطة\n\n";
    
    if ($can_purchase) {
        $text .= "✅ *يمكنك شراء هذا المنتج*";
    } else {
        $needed = $found_product['price'] - $user_points;
        $text .= "❌ *نقاطك غير كافية*\nتحتاج إلى {$needed} نقطة إضافية";
    }
    
    $keyboard = [];
    if ($can_purchase) {
        $keyboard[] = [['text' => '🛒 شراء الآن', 'callback_data' => 'buy_now_' . $found_product['code']]];
    } else {
        $keyboard[] = [['text' => '💰 تجميع النقاط', 'callback_data' => 'collect_points']];
    }
    
    $keyboard[] = [
        ['text' => '🔍 بحث مرة أخرى', 'callback_data' => 'search_product'],
        ['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']
    ];
    
    return bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== الهدية اليومية ==============
function giveDailyGift($chat_id, $message_id) {
    $users_data = loadData('users.json');
    $user_data = $users_data[$chat_id] ?? [];
    $today = date('Y-m-d');
    $last_gift = $user_data['last_gift'] ?? null;
    
    if ($last_gift == $today) {
        $text = "🎁 *الهدية اليومية*\n\nلقد حصلت بالفعل على هديتك اليومية اليوم!\nيرجى العودة غداً للحصول على هدية جديدة. ⏳";
        
        $keyboard = [
            [['text' => '💰 تجميع النقاط', 'callback_data' => 'collect_points']],
            [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
        ];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $gift_amount = rand(5, 15);
    $new_points = updateUserPoints($chat_id, $gift_amount, 'add');
    
    $users_data[$chat_id]['last_gift'] = $today;
    saveData('users.json', $users_data);
    
    $text = "🎁 *مبروك! لقد حصلت على هديتك اليومية*\n\n";
    $text .= "💰 *المبلغ:* {$gift_amount} نقطة\n";
    $text .= "💎 *إجمالي نقاطك:* {$new_points} نقطة\n\n";
    $text .= "🎯 يمكنك العودة غداً للحصول على هدية جديدة!";
    
    $keyboard = [
        [['text' => '🛒 تسوق الآن', 'callback_data' => 'grid_page_1']],
        [['text' => '💰 تجميع النقاط', 'callback_data' => 'collect_points']],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== تجميع النقاط ==============
function showPointsCollection($chat_id, $message_id) {
    $user_info = getUserInfo($chat_id);
    $me = bot('getMe', []);
    $bot_username = $me['result']['username'] ?? 'bot';
    $referral_link = "https://t.me/{$bot_username}?start={$chat_id}";
    
    $text = "💰 *تجميع النقاط*\n\n";
    $text .= "💎 نقاطك الحالية: *{$user_info['points']}* نقطة\n\n";
    $text .= "📊 *طرق تجميع النقاط:*\n";
    $text .= "1️⃣ *الدعوة:* احصل على 2 نقطة لكل صديق يدخل عبر رابطك\n";
    $text .= "2️⃣ *الشراء:* احصل على 10% من سعر المنتج كمكافأة\n";
    $text .= "3️⃣ *الهدايا:* احصل على 5-15 نقطة مجانية يومياً\n\n";
    $text .= "🔗 *رابط الدعوة الخاص بك:*\n";
    $text .= "{$referral_link}\n\n";
    $text .= "📌 *ملاحظة:* يجب أن يقوم صديقك بالضغط على /start أولاً";
    
    $keyboard = [
        [['text' => '📤 مشاركة الرابط', 'url' => "https://t.me/share/url?url=" . urlencode($referral_link) . "&text=" . urlencode("انضم إلي في متجر Cyber Idleb للحصول على منتجات رقمية مميزة! 🎁")]],
        [
            ['text' => '🎁 الهدية اليومية', 'callback_data' => 'daily_gift'],
            ['text' => '🛒 تصفح المتجر', 'callback_data' => 'grid_page_1']
        ],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== إحصائياتي ==============
function showUserStats($chat_id, $message_id) {
    $user_info = getUserInfo($chat_id);
    $stats = getBotStats();
    
    $text = "📊 *إحصائيات حسابك*\n\n";
    $text .= "👤 *المعلومات الشخصية:*\n";
    $text .= "👤 الاسم: {$user_info['name']}\n";
    $text .= "🆔 الأيدي: `{$chat_id}`\n";
    $text .= "👥 المعرف: @{$user_info['username']}\n";
    $text .= "📅 تاريخ الانضمام: {$user_info['join_date']}\n\n";
    
    $text .= "💰 *النقاط والإحصائيات:*\n";
    $text .= "💎 النقاط الحالية: *{$user_info['points']}* نقطة\n";
    $text .= "💸 النقاط المستخدمة: *{$user_info['used_points']}* نقطة\n";
    $text .= "👥 عدد الإحالات: *{$user_info['referrals']}* صديق\n";
    $text .= "🛒 عدد المشتريات: *{$user_info['purchases']}* عملية\n";
    $text .= "📈 نسبة المشتريات: " . ($stats['total_sales'] > 0 ? round(($user_info['purchases'] / $stats['total_sales']) * 100, 2) : 0) . "%\n\n";
    
    $text .= "📊 *إحصائيات عامة:*\n";
    $text .= "👥 إجمالي المستخدمين: *{$stats['total_users']}*\n";
    $text .= "👥 المستخدمين النشطين: *{$stats['active_users']}*\n";
    $text .= "🛒 إجمالي المبيعات: *{$stats['total_sales']}*\n";
    $text .= "🛒 مبيعات اليوم: *{$stats['today_sales']}*\n";
    $text .= "💰 النقاط الموزعة: *{$stats['distributed_points']}*\n";
    $text .= "💸 النقاط المستخدمة: *{$stats['used_points']}*\n";
    $text .= "📊 متوسط النقاط: *{$stats['avg_points']}*\n\n";
    
    $text .= "✨ استمر في التجميع والشراء!";
    
    $keyboard = [
        [['text' => '💰 تجميع النقاط', 'callback_data' => 'collect_points']],
        [['text' => '📟 تصفح المتجر', 'callback_data' => 'grid_page_1']],
        [['text' => '💸 تحويل نقاط', 'callback_data' => 'user_transfer_points']],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== مشترياتي ==============
function showMyPurchases($chat_id, $message_id) {
    $sales_data = loadData('sales.json');
    $purchases = $sales_data[$chat_id]['purchases'] ?? [];
    
    if (empty($purchases)) {
        $text = "🕸🕷 *مشترياتي*\n\nلم تقم بأي عملية شراء بعد.\nابدأ التسوق الآن! 🔖";
        
        $keyboard = [
            [['text' => '⌯ 📟 تصفح المتجر ⌯', 'callback_data' => 'grid_page_1']],
            [['text' => '⌯ 🏠 الرئيسية ⌯', 'callback_data' => 'main_menu']]
        ];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $text = "🛍️ *مشترياتي*\n\n";
    $text .= "📊 إجمالي المشتريات: " . count($purchases) . "\n\n";
    
    $counter = 1;
    $recent_purchases = array_slice(array_reverse($purchases), 0, 10);
    
    foreach ($recent_purchases as $purchase) {
        $status_emoji = '✅';
        $text .= "{$counter}. {$purchase['product_name']}\n";
        $text .= "   💰 السعر: {$purchase['price']} نقطة\n";
        $text .= "   📅 التاريخ: {$purchase['purchase_date']}\n";
        $text .= "   📋 الحالة: {$status_emoji} مكتمل\n";
        if (isset($purchase['purchase_id'])) {
            $text .= "   🆔 رقم الطلب: `{$purchase['purchase_id']}`\n";
        }
        if (isset($purchase['auto_proof']) && $purchase['auto_proof']) {
            $text .= "   📢 *إثبات تلقائي*\n";
        }
        $text .= "\n";
        $counter++;
    }
    
    if (count($purchases) > 10) {
        $text .= "📌 *عرض آخر 10 مشتريات فقط*\n";
    }
    
    $keyboard = [
        [['text' => '🛒 مواصلة التسوق', 'callback_data' => 'grid_page_1']],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== التواصل ==============
function showContactPage($chat_id, $message_id) {
    global $adminuz;
    
    $text = "📞 *تواصل معنا*\n\n";
    $text .= "👨‍💻 *الدعم الفني:*\n";
    $text .= "*المطور: MOOHAMED IDLEB X🥶 *\n\n";
    $text .= "📢 *قنواتنا:*\n";
    $text .= "*📰 خدمات جانبية :*\n";
    $text .= "🫰🏻 تعطيل حسابات مواقع تواصل  \n\n";
    $text .= "⏰ *أوقات العمل:*\n";
    $text .= "24/7 متاحون للرد على استفساراتكم";
    
    $keyboard = [
        [['text' => '👨‍💻 تواصل مع المطور', 'url' => "https://t.me/IDLEBX"]],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== لوحة الإدارة ==============
function showAdminPanel($chat_id, $message_id) {
    $stats = getBotStats();
    
    $text = "⚙️ *لوحة تحكم المشرف*\n\n";
    $text .= "📊 *إحصائيات البوت:*\n";
    $text .= "👥 إجمالي المستخدمين: *{$stats['total_users']}*\n";
    $text .= "👥 المستخدمين النشطين: *{$stats['active_users']}*\n";
    $text .= "🛒 إجمالي المبيعات: *{$stats['total_sales']}*\n";
    $text .= "🛒 مبيعات اليوم: *{$stats['today_sales']}*\n";
    $text .= "💰 النقاط الموزعة: *{$stats['distributed_points']}*\n";
    $text .= "💸 النقاط المستخدمة: *{$stats['used_points']}*\n";
    $text .= "📊 متوسط النقاط: *{$stats['avg_points']}*\n";
    $text .= "📦 عدد المنتجات: *{$stats['total_products']}*\n\n";
    $text .= "👇 *اختر من القائمة:*";
    
    $keyboard = [
        [
            ['text' => '⌯ 📦 المنتجات ⌯', 'callback_data' => 'manage_products'],
            ['text' => '⌯ 💰 النقاط ⌯', 'callback_data' => 'manage_points'],
            ['text' => '⌯ 👥 المستخدمين ⌯', 'callback_data' => 'manage_users']
        ],
        [
            ['text' => '⌯ 📢 الإذاعة ⌯', 'callback_data' => 'broadcast'],
            ['text' => '⌯ 🔍 بحث عن منتج ⌯', 'callback_data' => 'search_product'],
            ['text' => '⌯ 🏠 الرئيسية ⌯', 'callback_data' => 'main_menu']
        ]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== إدارة المنتجات ==============
function showProductManagement($chat_id, $message_id) {
    $products_data = loadData('products.json');
    $total_products = count($products_data['products'] ?? []);
    
    $text = "📦 *إدارة المنتجات*\n\n";
    $text .= "📟 عدد المنتجات: {$total_products}\n\n";
    $text .= "👇 *اختر الإجراء:*";
    
    $keyboard = [
        [
            ['text' => '➕ إضافة منتج', 'callback_data' => 'add_product'],
            ['text' => '📋 القائمة', 'callback_data' => 'list_products']
        ],
        [
            ['text' => '🗑️ حذف منتج', 'callback_data' => 'delete_product_list'],
            ['text' => '🔙 العودة', 'callback_data' => 'admin_panel']
        ]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function startAddProduct($chat_id, $message_id) {
    $products_data = loadData('products.json');
    $products_data['admin_mode'] = [
        'user_id' => $chat_id,
        'step' => 'product_name',
        'time' => time()
    ];
    saveData('products.json', $products_data);
    
    $text = "➕ *إضافة منتج جديد*\n\nأرسل اسم المنتج:";
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'manage_products']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function processAddProductStep($chat_id, $text) {
    $products_data = loadData('products.json');
    $admin_mode = $products_data['admin_mode'] ?? null;
    
    if (!$admin_mode || $admin_mode['user_id'] != $chat_id) {
        return;
    }
    
    $step = $admin_mode['step'];
    
    if ($step == 'product_name') {
        if (strlen($text) < 2) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ الاسم قصير جداً! أرسل اسم منتج أطول.",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $products_data['admin_temp'] = ['name' => $text];
        $products_data['admin_mode']['step'] = 'product_price';
        saveData('products.json', $products_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم حفظ الاسم*\n\nأرسل سعر المنتج (بالنقاط، رقم فقط):",
            'parse_mode' => 'Markdown'
        ]);
    } 
    elseif ($step == 'product_price') {
        if (!is_numeric($text) || $text <= 0) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ السعر غير صحيح! أرسل سعراً رقمياً أكبر من الصفر:",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $products_data['admin_temp']['price'] = (int)$text;
        $products_data['admin_mode']['step'] = 'product_description';
        saveData('products.json', $products_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم حفظ السعر*\n\nأرسل وصف للمنتج (اختياري):\nيمكنك إرسال 'تخطي' لتخطي هذه الخطوة",
            'parse_mode' => 'Markdown'
        ]);
    } 
    elseif ($step == 'product_description') {
        if (strtolower($text) != 'تخطي') {
            $products_data['admin_temp']['description'] = $text;
        } else {
            $products_data['admin_temp']['description'] = 'لا يوجد وصف';
        }
        
        $products_data['admin_mode']['step'] = 'product_file';
        saveData('products.json', $products_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم حفظ الوصف*\n\nالآن أرسل ملف المنتج (صورة، فيديو، صوت، ملف، أو أي نوع):",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => [
                [['text' => '❌ إلغاء', 'callback_data' => 'manage_products']]
            ]])
        ]);
    }
}

function processProductFile($chat_id, $message) {
    $products_data = loadData('products.json');
    $admin_mode = $products_data['admin_mode'] ?? null;
    
    if (!$admin_mode || $admin_mode['user_id'] != $chat_id) {
        return;
    }
    
    $file_id = null;
    $file_type = 'document';
    $emoji = '📦';
    
    if (isset($message['photo'])) {
        $photos = $message['photo'];
        $file_id = end($photos)['file_id'];
        $file_type = 'photo';
        $emoji = '☄️';
    } elseif (isset($message['document'])) {
        $file_id = $message['document']['file_id'];
        $file_type = 'document';
        $emoji = '🐺';
    } elseif (isset($message['audio'])) {
        $file_id = $message['audio']['file_id'];
        $file_type = 'audio';
        $emoji = '☁';
    } elseif (isset($message['video'])) {
        $file_id = $message['video']['file_id'];
        $file_type = 'video';
        $emoji = '🪦';
    } elseif (isset($message['voice'])) {
        $file_id = $message['voice']['file_id'];
        $file_type = 'voice';
        $emoji = '🔖';
    } elseif (isset($message['video_note'])) {
        $file_id = $message['video_note']['file_id'];
        $file_type = 'video_note';
        $emoji = '📺';
    } elseif (isset($message['sticker'])) {
        $file_id = $message['sticker']['file_id'];
        $file_type = 'sticker';
        $emoji = '🏷️';
    }
    
    if (!$file_id) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ لم يتم إرسال ملف صحيح! أرسل الملف مرة أخرى:",
            'parse_mode' => 'Markdown'
        ]);
        return;
    }
    
    $product_name = $products_data['admin_temp']['name'] ?? '';
    $product_price = $products_data['admin_temp']['price'] ?? 0;
    $product_description = $products_data['admin_temp']['description'] ?? 'لا يوجد وصف';
    $product_code = 'PD' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    $new_product = [
        'code' => $product_code,
        'name' => $product_name,
        'price' => $product_price,
        'description' => $product_description,
        'file_type' => $file_type,
        'file_id' => $file_id,
        'emoji' => $emoji,
        'added_date' => date('Y-m-d H:i:s'),
        'added_by' => $chat_id
    ];
    
    $products_data['products'][] = $new_product;
    unset($products_data['admin_mode']);
    unset($products_data['admin_temp']);
    saveData('products.json', $products_data);
    
    $text = "✅ *تمت إضافة المنتج بنجاح*\n\n";
    $text .= "🏷️ *الاسم:* {$product_name}\n";
    $text .= "💰 *السعر:* {$product_price} نقطة\n";
    $text .= "📁 *النوع:* {$file_type}\n";
    $text .= "🆔 *الكود:* `{$product_code}`\n";
    $text .= "📅 *تاريخ الإضافة:* " . date('Y-m-d H:i:s');
    
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => '📦 إدارة المنتجات', 'callback_data' => 'manage_products']]
        ]])
    ]);
}

function listProductsAdmin($chat_id, $message_id) {
    $products_data = loadData('products.json');
    $products = $products_data['products'] ?? [];
    
    if (empty($products)) {
        $text = "📦 *لا توجد منتجات*";
        $keyboard = [[['text' => '🔙 العودة', 'callback_data' => 'manage_products']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $text = "📋 *قائمة المنتجات*\n\n";
    $counter = 1;
    foreach ($products as $product) {
        $text .= "{$counter}. {$product['emoji']} *{$product['name']}*\n";
        $text .= "   💰 السعر: {$product['price']} نقطة\n";
        $text .= "   📁 النوع: {$product['file_type']}\n";
        $text .= "   🆔 الكود: `{$product['code']}`\n\n";
        $counter++;
    }
    
    $keyboard = [
        [['text' => '➕ إضافة منتج', 'callback_data' => 'add_product']],
        [['text' => '🗑️ حذف منتج', 'callback_data' => 'delete_product_list']],
        [['text' => '🔙 العودة', 'callback_data' => 'manage_products']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function showDeleteProductList($chat_id, $message_id) {
    $products_data = loadData('products.json');
    $products = $products_data['products'] ?? [];
    
    if (empty($products)) {
        $text = "🗑️ *لا توجد منتجات للحذف*";
        $keyboard = [[['text' => '🔙 العودة', 'callback_data' => 'manage_products']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $text = "🗑️ *حذف منتج*\n\n";
    $text .= "👇 *اختر المنتج الذي تريد حذفه:*\n\n";
    
    $keyboard = [];
    $counter = 1;
    
    foreach ($products as $index => $product) {
        $button_text = "{$counter}. {$product['emoji']} {$product['name']}";
        $callback_data = "confirm_delete_product_" . $product['code'];
        $keyboard[] = [['text' => $button_text, 'callback_data' => $callback_data]];
        $counter++;
    }
    
    $keyboard[] = [['text' => '🔙 العودة', 'callback_data' => 'manage_products']];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function confirmDeleteProduct($chat_id, $message_id, $product_code) {
    $products_data = loadData('products.json');
    
    $product = null;
    $product_index = -1;
    foreach ($products_data['products'] as $index => $p) {
        if ($p['code'] == $product_code) {
            $product = $p;
            $product_index = $index;
            break;
        }
    }
    
    if (!$product) {
        $text = "❌ *المنتج غير موجود*";
        $keyboard = [[['text' => '🔙 العودة', 'callback_data' => 'delete_product_list']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $text = "⚠️ *تأكيد حذف المنتج*\n\n";
    $text .= "هل أنت متأكد من حذف المنتج التالي؟\n\n";
    $text .= "🏷️ *الاسم:* {$product['name']}\n";
    $text .= "💰 *السعر:* {$product['price']} نقطة\n";
    $text .= "📁 *النوع:* {$product['file_type']}\n";
    $text .= "🆔 *الكود:* `{$product_code}`\n\n";
    $text .= "❗ *هذا الإجراء لا يمكن التراجع عنه*";
    
    $keyboard = [
        [
            ['text' => '✅ نعم، احذف المنتج', 'callback_data' => 'execute_delete_product_' . $product_code],
            ['text' => '❌ لا، إلغاء', 'callback_data' => 'delete_product_list']
        ]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function executeDeleteProduct($chat_id, $message_id, $product_code) {
    $products_data = loadData('products.json');
    
    $deleted = false;
    $product_name = '';
    foreach ($products_data['products'] as $index => $product) {
        if ($product['code'] == $product_code) {
            $product_name = $product['name'];
            unset($products_data['products'][$index]);
            $deleted = true;
            break;
        }
    }
    
    if ($deleted) {
        $products_data['products'] = array_values($products_data['products']);
        saveData('products.json', $products_data);
        
        $text = "✅ *تم حذف المنتج بنجاح*\n\n";
        $text .= "🏷️ المنتج المحذوف: *{$product_name}*\n";
        $text .= "🆔 كود المنتج: `{$product_code}`\n";
        $text .= "📅 تاريخ الحذف: " . date('Y-m-d H:i:s');
    } else {
        $text = "❌ *حدث خطأ أثناء حذف المنتج*\n\nلم يتم العثور على المنتج المحدد.";
    }
    
    $keyboard = [
        [['text' => '📦 إدارة المنتجات', 'callback_data' => 'manage_products']],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== إدارة النقاط ==============
function showPointsManagement($chat_id, $message_id) {
    $stats = getBotStats();
    
    $text = "💰 *إدارة النقاط*\n\n";
    $text .= "💰 النقاط الموزعة: *{$stats['distributed_points']}* نقطة\n";
    $text .= "💸 النقاط المستخدمة: *{$stats['used_points']}* نقطة\n";
    $text .= "📊 متوسط النقاط: *{$stats['avg_points']}* نقطة\n\n";
    $text .= "👇 *اختر الإجراء:*";
    
    $keyboard = [
        [
            ['text' => '➕ إضافة نقاط', 'callback_data' => 'add_points'],
            ['text' => '➖ خصم نقاط', 'callback_data' => 'subtract_points']
        ],
        [
            ['text' => '🔄 تحويل نقاط', 'callback_data' => 'transfer_points'],
            ['text' => '📊 عرض نقاط', 'callback_data' => 'view_points']
        ],
        [
            ['text' => '🔙 العودة', 'callback_data' => 'admin_panel']
        ]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function startAddPoints($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['admin_mode'] = [
        'user_id' => $chat_id,
        'step' => 'points_user',
        'action' => 'add',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "➕ *إضافة نقاط*\n\nأرسل أيدي المستخدم:";
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'manage_points']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function startSubtractPoints($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['admin_mode'] = [
        'user_id' => $chat_id,
        'step' => 'points_user',
        'action' => 'subtract',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "➖ *خصم نقاط*\n\nأرسل أيدي المستخدم:";
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'manage_points']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function startTransferPoints($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['admin_mode'] = [
        'user_id' => $chat_id,
        'step' => 'transfer_from_user',
        'action' => 'transfer',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "🔄 *تحويل نقاط (للمشرف)*\n\nأرسل أيدي المرسل:";
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'manage_points']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function processPointsStep($chat_id, $text) {
    $admin_data = loadData('admin.json');
    $admin_mode = $admin_data['admin_mode'] ?? null;
    
    if (!$admin_mode || $admin_mode['user_id'] != $chat_id) {
        return;
    }
    
    $step = $admin_mode['step'];
    $action = $admin_mode['action'] ?? 'add';
    
    if ($step == 'points_user') {
        if (!is_numeric($text)) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ أيدي غير صحيح! أرسل أيدي المستخدم (رقم فقط):",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $admin_data['admin_temp'] = ['user_id' => (int)$text];
        $admin_data['admin_mode']['step'] = 'points_amount';
        saveData('admin.json', $admin_data);
        
        $action_text = ($action == 'add') ? 'إضافة' : 'خصم';
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم حفظ الأيدي*\n\nأرسل عدد النقاط لل{$action_text}:",
            'parse_mode' => 'Markdown'
        ]);
    } 
    elseif ($step == 'points_amount') {
        if (!is_numeric($text) || $text <= 0) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ عدد غير صحيح! أرسل عدد النقاط (رقم أكبر من الصفر):",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $user_id = $admin_data['admin_temp']['user_id'];
        $amount = (int)$text;
        
        if ($action == 'add') {
            $new_points = updateUserPoints($user_id, $amount, 'add', $chat_id, true);
            $message = "✅ *تمت الإضافة بنجاح*\n\n👤 المستخدم: {$user_id}\n💰 النقاط المضافة: {$amount}\n💎 النقاط الجديدة: {$new_points}\n\n📢 *تم إرسال إشعار للمستخدم*";
        } else {
            $new_points = updateUserPoints($user_id, $amount, 'subtract');
            $message = "✅ *تم الخصم بنجاح*\n\n👤 المستخدم: {$user_id}\n💰 النقاط المخصومة: {$amount}\n💎 النقاط الجديدة: {$new_points}";
        }
        
        unset($admin_data['admin_mode']);
        unset($admin_data['admin_temp']);
        saveData('admin.json', $admin_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => [
                [['text' => '💰 إدارة النقاط', 'callback_data' => 'manage_points']]
            ]])
        ]);
    }
    elseif ($step == 'transfer_from_user') {
        if (!is_numeric($text)) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ أيدي غير صحيح! أرسل أيدي المرسل (رقم فقط):",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $admin_data['admin_temp'] = ['from_user_id' => (int)$text];
        $admin_data['admin_mode']['step'] = 'transfer_to_user';
        saveData('admin.json', $admin_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم حفظ أيدي المرسل*\n\nأرسل أيدي المستلم:",
            'parse_mode' => 'Markdown'
        ]);
    }
    elseif ($step == 'transfer_to_user') {
        if (!is_numeric($text)) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ أيدي غير صحيح! أرسل أيدي المستلم (رقم فقط):",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $admin_data['admin_temp']['to_user_id'] = (int)$text;
        $admin_data['admin_mode']['step'] = 'transfer_amount';
        saveData('admin.json', $admin_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ *تم حفظ أيدي المستلم*\n\nأرسل عدد النقاط للتحويل:",
            'parse_mode' => 'Markdown'
        ]);
    }
    elseif ($step == 'transfer_amount') {
        if (!is_numeric($text) || $text <= 0) {
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ عدد غير صحيح! أرسل عدد النقاط (رقم أكبر من الصفر):",
                'parse_mode' => 'Markdown'
            ]);
            return;
        }
        
        $from_user_id = $admin_data['admin_temp']['from_user_id'];
        $to_user_id = $admin_data['admin_temp']['to_user_id'];
        $amount = (int)$text;
        
        $from_points = getUserPoints($from_user_id);
        
        if ($from_points < $amount) {
            $message = "❌ *فشل التحويل*\n\n👤 المرسل: {$from_user_id}\n💎 رصيده الحالي: {$from_points} نقطة\n💰 المبلغ المطلوب: {$amount} نقطة\n\nالمرسل لا يملك نقاط كافية!";
        } else {
            $new_from_points = updateUserPoints($from_user_id, $amount, 'subtract');
            $new_to_points = updateUserPoints($to_user_id, $amount, 'add', $chat_id, true);
            
            $message = "✅ *تم التحويل بنجاح*\n\n";
            $message .= "👤 المرسل: {$from_user_id}\n";
            $message .= "👤 المستلم: {$to_user_id}\n";
            $message .= "💰 المبلغ المحول: {$amount} نقطة\n";
            $message .= "💎 رصيد المرسل الجديد: {$new_from_points} نقطة\n";
            $message .= "💎 رصيد المستلم الجديد: {$new_to_points} نقطة";
            
            bot('sendMessage', [
                'chat_id' => $from_user_id,
                'text' => "🔁 *تم تحويل نقاط من حسابك*\n\n💰 المبلغ المحول: {$amount} نقطة\n👤 للمستخدم: {$to_user_id}\n💎 رصيدك الحالي: {$new_from_points} نقطة",
                'parse_mode' => 'Markdown'
            ]);
        }
        
        unset($admin_data['admin_mode']);
        unset($admin_data['admin_temp']);
        saveData('admin.json', $admin_data);
        
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => [
                [['text' => '💰 إدارة النقاط', 'callback_data' => 'manage_points']]
            ]])
        ]);
    }
}

function startViewPoints($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['admin_mode'] = [
        'user_id' => $chat_id,
        'step' => 'view_points_user',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "📊 *عرض نقاط مستخدم*\n\nأرسل أيدي المستخدم:";
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'manage_points']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function processViewPoints($chat_id, $text) {
    if (!is_numeric($text)) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ أيدي غير صحيح!",
            'parse_mode' => 'Markdown'
        ]);
        return;
    }
    
    $user_id = (int)$text;
    $points = getUserPoints($user_id);
    $user_info = getUserInfo($user_id);
    
    $admin_data = loadData('admin.json');
    unset($admin_data['admin_mode']);
    saveData('admin.json', $admin_data);
    
    $text = "📊 *نقاط المستخدم*\n\n";
    $text .= "👤 المستخدم: @{$user_info['username']}\n";
    $text .= "🆔 الأيدي: {$user_id}\n";
    $text .= "💎 النقاط الحالية: {$points} نقطة\n";
    $text .= "💸 النقاط المستخدمة: {$user_info['used_points']} نقطة\n";
    $text .= "👥 عدد الأحالة: {$user_info['referrals']}\n";
    $text .= "🛒 عدد المشتريات: {$user_info['purchases']}\n";
    $text .= "📅 تاريخ الانضمام: {$user_info['join_date']}\n\n";
    $text .= "💡 *للإدارة:*";
    
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => '➕ إضافة نقاط', 'callback_data' => 'add_points']],
            [['text' => '➖ خصم نقاط', 'callback_data' => 'subtract_points']],
            [['text' => '🔄 تحويل نقاط', 'callback_data' => 'transfer_points']],
            [['text' => '💰 إدارة النقاط', 'callback_data' => 'manage_points']]
        ]])
    ]);
}

// ============== الإذاعة ==============
function startBroadcast($chat_id, $message_id) {
    $admin_data = loadData('admin.json');
    $admin_data['admin_mode'] = [
        'user_id' => $chat_id,
        'step' => 'broadcast',
        'time' => time()
    ];
    saveData('admin.json', $admin_data);
    
    $text = "📢 *الإذاعة*\n\nأرسل النص للإذاعة:";
    $keyboard = [[['text' => '❌ إلغاء', 'callback_data' => 'admin_panel']]];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function processBroadcast($chat_id, $text) {
    $users_data = loadData('users.json');
    $users = array_keys($users_data);
    $total = count($users);
    
    if ($total == 0) {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ لا يوجد مستخدمين للإرسال!",
            'parse_mode' => 'Markdown'
        ]);
        return;
    }
    
    $success = 0;
    $failed = 0;
    
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "*📤 ✅ جاري الإرسال...*\n\nإجمالي المستخدمين: {$total}\nيرجى الانتظار...",
        'parse_mode' => 'Markdown'
    ]);
    
    foreach ($users as $user_id) {
        try {
            $result = bot('sendMessage', [
                'chat_id' => $user_id,
                'text' => "📢 *إذاعة من الإدارة*\n\n{$text}\n\n✨ *متجر الضل الرقمي*",
                'parse_mode' => 'Markdown'
            ]);
            
            if ($result['ok']) {
                $success++;
            } else {
                $failed++;
            }
            
            usleep(100000);
            
        } catch (Exception $e) {
            $failed++;
        }
    }
    
    $admin_data = loadData('admin.json');
    unset($admin_data['admin_mode']);
    saveData('admin.json', $admin_data);
    
    $text = "✅ *تم الإرسال بنجاح*\n\n📊 تم الإرسال لـ {$success} من {$total} مستخدم\n❌ فشل: {$failed}";
    
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => '📢 الإذاعة', 'callback_data' => 'broadcast']],
            [['text' => '🔙 العودة', 'callback_data' => 'admin_panel']]
        ]])
    ]);
}

// ============== إدارة المستخدمين ==============
function showUserManagement($chat_id, $message_id) {
    $users_data = loadData('users.json');
    $total_users = count($users_data);
    $stats = getBotStats();
    
    $text = "👥 *إدارة المستخدمين*\n\n";
    $text .= "👤 إجمالي المستخدمين: {$total_users}\n";
    $text .= "👥 المستخدمين النشطين: {$stats['active_users']}\n";
    $text .= "📊 متوسط النقاط: {$stats['avg_points']}\n\n";
    $text .= "👇 *اختر الإجراء:*";
    
    $keyboard = [
        [['text' => '📊 قائمة المستخدمين', 'callback_data' => 'list_users']],
        [['text' => '🔙 العودة', 'callback_data' => 'admin_panel']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function listUsersAdmin($chat_id, $message_id) {
    $users_data = loadData('users.json');
    $total_users = count($users_data);
    
    if (empty($users_data)) {
        $text = "👥 *لا يوجد مستخدمين*";
        $keyboard = [[['text' => '🔙 العودة', 'callback_data' => 'manage_users']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $total_points = 0;
    $total_purchases = 0;
    $sales_data = loadData('sales.json');
    
    foreach ($users_data as $user_id => $user_info) {
        $total_points += $user_info['points'] ?? 0;
        if (isset($sales_data[$user_id]['purchases'])) {
            $total_purchases += count($sales_data[$user_id]['purchases']);
        }
    }
    
    $avg_points = $total_users > 0 ? round($total_points / $total_users, 2) : 0;
    
    $text = "📋 *قائمة المستخدمين*\n\n";
    $text .= "👤 إجمالي المستخدمين: {$total_users}\n";
    $text .= "💰 إجمالي النقاط: {$total_points} نقطة\n";
    $text .= "📊 متوسط النقاط: {$avg_points} نقطة\n";
    $text .= "🛒 إجمالي المشتريات: {$total_purchases}\n\n";
    $text .= "*آخر 10 مستخدمين:*\n\n";
    
    $counter = 1;
    $recent_users = array_slice($users_data, -10, 10, true);
    
    foreach ($recent_users as $user_id => $user_info) {
        $points = $user_info['points'] ?? 0;
        $purchases = 0;
        if (isset($sales_data[$user_id]['purchases'])) {
            $purchases = count($sales_data[$user_id]['purchases']);
        }
        
        $text .= "{$counter}. @{$user_info['username']}\n";
        $text .= "   👤 الاسم: {$user_info['name']}\n";
        $text .= "   🆔 الأيدي: `{$user_id}`\n";
        $text .= "   💎 النقاط: {$points}\n";
        $text .= "   🛒 المشتريات: {$purchases}\n";
        $text .= "   📅 الانضمام: " . substr($user_info['join_date'], 0, 10) . "\n\n";
        $counter++;
    }
    
    $keyboard = [
        [['text' => '📊 عرض جميع المستخدمين', 'callback_data' => 'all_users']],
        [['text' => '🔙 العودة', 'callback_data' => 'manage_users']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function showAllUsers($chat_id, $message_id) {
    $users_data = loadData('users.json');
    $total_users = count($users_data);
    
    if (empty($users_data)) {
        $text = "👥 *لا يوجد مستخدمين*";
        $keyboard = [[['text' => '🔙 العودة', 'callback_data' => 'list_users']]];
        
        return bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    
    $text = "📋 *جميع المستخدمين*\n\n";
    $text .= "👤 إجمالي المستخدمين: {$total_users}\n\n";
    
    $counter = 1;
    foreach ($users_data as $user_id => $user_info) {
        $points = $user_info['points'] ?? 0;
        $text .= "{$counter}. @{$user_info['username']}";
        $text .= " (ID: `{$user_id}`)";
        $text .= " - {$points} نقطة\n";
        $counter++;
        
        if ($counter > 50) {
            $text .= "\n📌 *عرض أول 50 مستخدم فقط*\n";
            break;
        }
    }
    
    $keyboard = [
        [['text' => '🔙 العودة للقائمة', 'callback_data' => 'list_users']],
        [['text' => '🏠 الرئيسية', 'callback_data' => 'main_menu']]
    ];
    
    return bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ============== المعالج الرئيسي ==============
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    if (isset($_GET['test'])) {
        echo "Bot is working!";
    }
    exit;
}

$message = $update['message'] ?? null;
$callback_query = $update['callback_query'] ?? null;

$chat_id = $message['chat']['id'] ?? $callback_query['message']['chat']['id'] ?? null;
$from_id = $message['from']['id'] ?? $callback_query['from']['id'] ?? null;
$text = $message['text'] ?? null;
$message_id = $callback_query['message']['message_id'] ?? null;
$data = $callback_query['data'] ?? null;
$callback_query_id = $callback_query['id'] ?? null;
$username = $message['from']['username'] ?? $callback_query['from']['username'] ?? '';
$name = $message['from']['first_name'] ?? $callback_query['from']['first_name'] ?? '';

if (!$from_id || !$chat_id) {
    exit;
}

$is_admin = in_array($from_id, $sudo);

// معالجة الرسائل النصية
if ($message && $text) {
    // أمر /start
    if (strpos($text, '/start') === 0) {
        // التحقق من الاشتراك أولاً
        if (!checkChannelSubscription($from_id) && !$is_admin) {
            showChannelsMenu($chat_id);
            exit;
        }
        
        $parts = explode(' ', $text);
        if (count($parts) > 1 && is_numeric($parts[1]) && $parts[1] != $from_id) {
            $referrer_id = $parts[1];
            
            $users_data = loadData('users.json');
            if (isset($users_data[$referrer_id])) {
                $referrer_points = updateUserPoints($referrer_id, 2, 'add');
                
                $sales_data = loadData('sales.json');
                if (!isset($sales_data[$referrer_id]['referrals'])) {
                    $sales_data[$referrer_id]['referrals'] = [];
                }
                
                if (!in_array($from_id, $sales_data[$referrer_id]['referrals'])) {
                    $sales_data[$referrer_id]['referrals'][] = $from_id;
                    saveData('sales.json', $sales_data);
                    
                    bot('sendMessage', [
                        'chat_id' => $referrer_id,
                        'text' => "⚡ *قام شخص بالدخول عبر رابط الدعوة الخاص بك* ⚡\n\n👤 المستخدم: @{$username}\n💰 النقاط المضافة: 2 نقطة\n💎 إجمالي نقاطك: {$referrer_points} نقطة",
                        'parse_mode' => 'Markdown'
                    ]);
                }
            }
        }
        
        addNewUser($from_id, $username, $name);
        showMainMenu($chat_id, null, true);
        exit;
    }
    
    // معالجة وضع تحويل النقاط للمستخدمين
    $admin_data = loadData('admin.json');
    if (isset($admin_data['user_transfer_mode']) && $admin_data['user_transfer_mode']['user_id'] == $from_id) {
        if (processUserTransferPoints($from_id, $text)) {
            exit;
        }
    }
    
    // معالجة وضع البحث عن المنتج
    if (isset($admin_data['search_mode']) && $admin_data['search_mode']['user_id'] == $from_id && $admin_data['search_mode']['step'] == 'search_product') {
        searchProductByCode($from_id, $text);
        exit;
    }
    
    // معالجة وضع الإدارة
    if ($is_admin) {
        $admin_data = loadData('admin.json');
        $admin_mode = $admin_data['admin_mode'] ?? null;
        
        if ($admin_mode && $admin_mode['user_id'] == $from_id) {
            $step = $admin_mode['step'] ?? '';
            
            if (in_array($step, ['points_user', 'points_amount', 'transfer_from_user', 'transfer_to_user', 'transfer_amount'])) {
                processPointsStep($from_id, $text);
                exit;
            }
            
            if ($step == 'broadcast') {
                processBroadcast($from_id, $text);
                exit;
            }
            
            if ($step == 'view_points_user') {
                processViewPoints($from_id, $text);
                exit;
            }
        }
        
        $products_data = loadData('products.json');
        $products_admin_mode = $products_data['admin_mode'] ?? null;
        
        if ($products_admin_mode && $products_admin_mode['user_id'] == $from_id) {
            $step = $products_admin_mode['step'] ?? '';
            
            if (in_array($step, ['product_name', 'product_price', 'product_description'])) {
                processAddProductStep($from_id, $text);
                exit;
            }
        }
    }
}

// معالجة الملفات (لإضافة المنتجات)
if ($message && ($message['photo'] || $message['document'] || $message['audio'] || $message['video'] || $message['voice'] || $message['video_note'] || $message['sticker'])) {
    if ($is_admin) {
        $products_data = loadData('products.json');
        $admin_mode = $products_data['admin_mode'] ?? null;
        
        if ($admin_mode && $admin_mode['user_id'] == $from_id && $admin_mode['step'] == 'product_file') {
            processProductFile($from_id, $message);
            exit;
        }
    }
}

// معالجة الكويريز
if ($callback_query && $data) {
    bot('answerCallbackQuery', ['callback_query_id' => $callback_query_id]);
    
    switch ($data) {
        // القائمة الرئيسية
        case 'main_menu':
            showMainMenu($chat_id, $message_id);
            break;
            
        // المتجر
        case 'grid_page_1':
            showGridPage($chat_id, $message_id, 1);
            break;
            
        // تجميع النقاط
        case 'collect_points':
            showPointsCollection($chat_id, $message_id);
            break;
            
        // إحصائياتي
        case 'my_stats':
            showUserStats($chat_id, $message_id);
            break;
            
        // الهدية اليومية
        case 'daily_gift':
            giveDailyGift($chat_id, $message_id);
            break;
            
        // مشترياتي
        case 'my_purchases':
            showMyPurchases($chat_id, $message_id);
            break;
            
        // التواصل
        case 'contact_us':
            showContactPage($chat_id, $message_id);
            break;
            
        // تحويل نقاط للمستخدمين
        case 'user_transfer_points':
            startUserTransferPoints($chat_id, $message_id);
            break;
            
        // بحث عن منتج
        case 'search_product':
            showProductSearch($chat_id, $message_id);
            break;
            
        // لوحة الإدارة
        case 'admin_panel':
            if ($is_admin) showAdminPanel($chat_id, $message_id);
            break;
            
        // إدارة المنتجات
        case 'manage_products':
            if ($is_admin) showProductManagement($chat_id, $message_id);
            break;
            
        case 'add_product':
            if ($is_admin) startAddProduct($chat_id, $message_id);
            break;
            
        case 'list_products':
            if ($is_admin) listProductsAdmin($chat_id, $message_id);
            break;
            
        case 'delete_product_list':
            if ($is_admin) showDeleteProductList($chat_id, $message_id);
            break;
            
        // إدارة النقاط
        case 'manage_points':
            if ($is_admin) showPointsManagement($chat_id, $message_id);
            break;
            
        case 'add_points':
            if ($is_admin) startAddPoints($chat_id, $message_id);
            break;
            
        case 'subtract_points':
            if ($is_admin) startSubtractPoints($chat_id, $message_id);
            break;
            
        case 'transfer_points':
            if ($is_admin) startTransferPoints($chat_id, $message_id);
            break;
            
        case 'view_points':
            if ($is_admin) startViewPoints($chat_id, $message_id);
            break;
            
        // الإذاعة
        case 'broadcast':
            if ($is_admin) startBroadcast($chat_id, $message_id);
            break;
            
        // إدارة المستخدمين
        case 'manage_users':
            if ($is_admin) showUserManagement($chat_id, $message_id);
            break;
            
        case 'list_users':
            if ($is_admin) listUsersAdmin($chat_id, $message_id);
            break;
            
        case 'all_users':
            if ($is_admin) showAllUsers($chat_id, $message_id);
            break;
            
        // التحقق من الاشتراك
        case 'check_subscription':
            if (checkChannelSubscription($from_id)) {
                addNewUser($from_id, $username, $name);
                showMainMenu($chat_id, $message_id);
            } else {
                bot('answerCallbackQuery', [
                    'callback_query_id' => $callback_query_id,
                    'text' => '❌ لم تشترك بعد في جميع القنوات',
                    'show_alert' => true
                ]);
            }
            break;
            
        // التنقل بين صفحات المتجر
        default:
            if (strpos($data, 'grid_page_') === 0) {
                $page = (int)str_replace('grid_page_', '', $data);
                showGridPage($chat_id, $message_id, $page);
            } 
            elseif (strpos($data, 'product_') === 0) {
                $product_code = str_replace('product_', '', $data);
                showProductDetails($chat_id, $message_id, $product_code);
            } 
            elseif (strpos($data, 'buy_now_') === 0) {
                $product_code = str_replace('buy_now_', '', $data);
                $result = processPurchase($chat_id, $message_id, $product_code);
                
                if (isset($result['error'])) {
                    bot('answerCallbackQuery', [
                        'callback_query_id' => $callback_query_id,
                        'text' => $result['error'],
                        'show_alert' => true
                    ]);
                }
            }
            elseif (strpos($data, 'confirm_delete_product_') === 0) {
                $product_code = str_replace('confirm_delete_product_', '', $data);
                confirmDeleteProduct($chat_id, $message_id, $product_code);
            }
            elseif (strpos($data, 'execute_delete_product_') === 0) {
                $product_code = str_replace('execute_delete_product_', '', $data);
                executeDeleteProduct($chat_id, $message_id, $product_code);
            }
            break;
    }
}

// تنظيف وضع الإدارة بعد 5 دقائق من عدم النشاط
$admin_data = loadData('admin.json');
if (isset($admin_data['admin_mode'])) {
    $admin_mode = $admin_data['admin_mode'];
    if (time() - ($admin_mode['time'] ?? 0) > 300) {
        unset($admin_data['admin_mode']);
        if (isset($admin_data['admin_temp'])) {
            unset($admin_data['admin_temp']);
        }
        saveData('admin.json', $admin_data);
    }
}

if (isset($admin_data['user_transfer_mode'])) {
    $transfer_mode = $admin_data['user_transfer_mode'];
    if (time() - ($transfer_mode['time'] ?? 0) > 300) {
        unset($admin_data['user_transfer_mode']);
        if (isset($admin_data['user_transfer_temp'])) {
            unset($admin_data['user_transfer_temp']);
        }
        saveData('admin.json', $admin_data);
    }
}

if (isset($admin_data['search_mode'])) {
    $search_mode = $admin_data['search_mode'];
    if (time() - ($search_mode['time'] ?? 0) > 300) {
        unset($admin_data['search_mode']);
        saveData('admin.json', $admin_data);
    }
}

$products_data = loadData('products.json');
if (isset($products_data['admin_mode'])) {
    $admin_mode = $products_data['admin_mode'];
    if (time() - ($admin_mode['time'] ?? 0) > 300) {
        unset($products_data['admin_mode']);
        if (isset($products_data['admin_temp'])) {
            unset($products_data['admin_temp']);
        }
        saveData('products.json', $products_data);
    }
}
?>

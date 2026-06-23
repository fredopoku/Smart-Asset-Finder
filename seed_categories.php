<?php
require_once('config.php');

// Only allow admin or localhost
$allowed = ['127.0.0.1', '::1', 'localhost'];
if(!in_array($_SERVER['REMOTE_ADDR'], $allowed)){
    http_response_code(403); die('Forbidden');
}

$categories = [
    ['Bags & Luggage',        'Backpacks, handbags, suitcases, duffel bags, and travel luggage'],
    ['Books & Stationery',    'Books, notebooks, planners, pens, and stationery items'],
    ['Cameras & Photography', 'Digital cameras, film cameras, lenses, memory cards, and accessories'],
    ['Clothing & Fashion',    'Jackets, scarves, hats, belts, shoes, and clothing items'],
    ['Computers & Laptops',   'Laptops, MacBooks, Chromebooks, desktop equipment, and peripherals'],
    ['Credit Cards & Banking','Bank cards, credit cards, and financial cards'],
    ['Documents & IDs',       'Passports, national IDs, driving licences, student cards, and certificates'],
    ['Electronics',           'Chargers, power banks, earbuds, smartwatches, cables, and miscellaneous electronics'],
    ['Glasses & Eyewear',     'Prescription glasses, sunglasses, reading glasses, and contact lens cases'],
    ['Headphones & Audio',    'Earphones, headphones, speakers, and audio accessories'],
    ['Jewellery',             'Rings, necklaces, bracelets, earrings, and precious accessories'],
    ['Keys',                  'House keys, car keys, office keys, and key fobs'],
    ['Medical & Health',      'Inhalers, hearing aids, prescription medication, and medical devices'],
    ['Mobile Phones',         'Smartphones and mobile phones of all brands'],
    ['Musical Instruments',   'Guitars, violins, flutes, keyboards, and musical accessories'],
    ['Pets & Animals',        'Lost pets, collars, pet tags, and animal-related items'],
    ['Sports & Fitness',      'Gym equipment, sports gear, bicycles, helmets, and fitness accessories'],
    ['Tablets & E-readers',   'iPads, Android tablets, Kindles, and e-readers'],
    ['Tools & Equipment',     'Hand tools, power tools, professional equipment, and work gear'],
    ['Toys & Children\'s Items','Toys, children\'s bags, school items, and kids accessories'],
    ['Umbrellas',             'Umbrellas of all types'],
    ['Vehicles & Transport',  'Bicycles, scooters, vehicle keys, parking tickets, and transport items'],
    ['Wallets & Purses',      'Wallets, coin purses, cardholders, and money clips'],
    ['Watches',               'Wristwatches, smartwatches, and timepieces'],
    ['Other',                 'Items that do not fit any other category'],
];

$inserted = 0;
$skipped  = 0;

$check = $conn->prepare("SELECT id FROM category_list WHERE name=? LIMIT 1");
$insert = $conn->prepare("INSERT INTO category_list (name, description, status) VALUES (?,?,1)");

foreach($categories as [$name, $desc]){
    $check->bind_param('s', $name);
    $check->execute();
    $check->store_result();
    if($check->num_rows > 0){ $skipped++; continue; }
    $insert->bind_param('ss', $name, $desc);
    $insert->execute();
    $inserted++;
}
$check->close();
$insert->close();
?>
<!DOCTYPE html>
<html>
<head><title>Category Seed</title>
<style>body{font-family:system-ui,sans-serif;max-width:500px;margin:4rem auto;padding:0 1rem;color:#0f172a}
.ok{color:#059669;font-weight:700}.info{color:#64748b;font-size:.9rem}</style>
</head>
<body>
<h2>✅ Category Seed Complete</h2>
<p class="ok"><?= $inserted ?> categories added</p>
<p class="info"><?= $skipped ?> already existed and were skipped</p>
<p class="info" style="margin-top:2rem">
  <strong>Delete this file now:</strong><br>
  <code style="background:#f1f5f9;padding:.2rem .5rem;border-radius:4px">rm /Applications/XAMPP/htdocs/Smart-Asset-Finder/seed_categories.php</code>
</p>
<p><a href="/Smart-Asset-Finder/?page=items" style="color:#4f46e5">→ Browse Items</a> &nbsp; <a href="/Smart-Asset-Finder/admin/" style="color:#4f46e5">→ Admin</a></p>
</body>
</html>

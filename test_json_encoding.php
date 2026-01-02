<?php
// Test script to verify JSON encoding handles newlines properly

$testData = [
    'reason' => "Regular schedule - Tuesday, Wednesday, Thursday and Friday. (7;00am to 6:00pm)

Day off - Monday, Saturday and Sunday.",
    'explanation' => 'Test'
];

echo "=== WITHOUT JSON_HEX_TAG (BROKEN) ===\n";
$brokenJson = htmlspecialchars(json_encode($testData, JSON_HEX_APOS | JSON_HEX_QUOT));
echo $brokenJson . "\n\n";
echo "Attempting to parse in JavaScript will FAIL due to newline\n\n";

echo "=== WITH JSON_HEX_TAG (FIXED) ===\n";
$fixedJson = htmlspecialchars(json_encode($testData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG));
echo $fixedJson . "\n\n";
echo "This can be safely parsed in JavaScript\n\n";

echo "=== HTML OUTPUT TEST ===\n";
echo "<button onclick='testParse(`$fixedJson`)'>Click to test</button>\n";
echo "<script>\n";
echo "function testParse(json) {\n";
echo "    try {\n";
echo "        const data = JSON.parse(json);\n";
echo "        console.log('✅ Parsed successfully:', data);\n";
echo "        alert('Success! Reason: ' + data.reason);\n";
echo "    } catch(e) {\n";
echo "        console.error('❌ Parse failed:', e);\n";
echo "        alert('Failed: ' + e.message);\n";
echo "    }\n";
echo "}\n";
echo "</script>\n";
?>

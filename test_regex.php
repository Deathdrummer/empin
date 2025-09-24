<?php

$html = '<!DOCTYPE html><html><head><title>Index of /</title><script>function srt(tb, sc, so, d) {var tr = Array.prototype.slice.call(tb.rows, 0),tr = tr.sort(function (a, b) { var c1 = a.cells[sc], c2 = b.cells[sc],n1 = c1.getAttribute(\'name\'), n2 = c2.getAttribute(\'name\'), t1 = a.cells[2].getAttribute(\'name\'), t2 = b.cells[2].getAttribute(\'name\'); return so * (t1 < 0 && t2 >= 0 ? -1 : t2 < 0 && t1 >= 0 ? 1 : n1 ? parseInt(n2) - parseInt(n1) : c1.textContent.trim().localeCompare(c2.textContent.trim())); });for (var i = 0; i < tr.length; i++) tb.appendChild(tr[i]); if (!d) window.location.hash = (\'sc=\' + sc + \'&so=\' + so); };window.onload = function() {var tb = document.getElementById(\'tb\');var m = /sc=([012]).so=(1|-1)/.exec(window.location.hash) || [0, 2, 1];var sc = m[1], so = m[2]; document.onclick = function(ev) { var c = ev.target.rel; if (c) {if (c == sc) so *= -1; srt(tb, c, so); sc = c; ev.preventDefault();}};srt(tb, sc, so, true);}</script><style>th,td {text-align: left; padding-right: 1em; font-family: monospace; }</style></head><body><h1>Index of /</h1><table cellpadding="0"><thead><tr><th><a href="#" rel="0">Name</a></th><th><a href="#" rel="1">Modified</a></th><th><a href="#" rel="2">Size</a></th></tr><tr><td colspan="3"><hr></td></tr></thead><tbody id="tb">
  <tr><td><a href="..">..</a></td><td name=-1></td><td name=-1>[DIR]</td></tr>
  <tr><td><a href="CAD_SIMPLE.bat">CAD_SIMPLE.bat</a></td><td name=1758701103>2025/09/24 11:05:03</td><td name=3883>3883</td></tr>
  <tr><td><a href="mongoose.exe">mongoose.exe</a></td><td name=1758698733>2025/09/24 10:25:33</td><td name=110592>110592</td></tr>
  <tr><td><a href="test.dwg">test.dwg</a></td><td name=1758601852>2025/09/23 07:30:52</td><td name=37070>37070</td></tr>
  <tr><td><a href="test_dxfighter.dxf">test_dxfighter.dxf</a></td><td name=1758614086>2025/09/23 10:54:46</td><td name=11656>11656</td></tr>
  <tr><td><a href="test_out.dxf">test_out.dxf</a></td><td name=1758633004>2025/09/23 16:10:04</td><td name=13662>13662</td></tr>
</tbody><tfoot><tr><td colspan="3"><hr></td></tr></tfoot></table><address>Mongoose v.7.18</address></body></html>';

echo "Testing regex...\n";
preg_match_all('/<a[^>]+href=["\']?([^"\'>\s]*\.(?:dwg|dxf))["\']?[^>]*>([^<]+)<\/a>/i', $html, $matches);

echo "Matches found: " . count($matches[1]) . "\n";
print_r($matches[1]);

// Попробуем более простой регекс
echo "\nTesting simple regex...\n";
preg_match_all('/<a[^>]+href="([^"]+\.(?:dwg|dxf))"/', $html, $simple_matches);
echo "Simple matches found: " . count($simple_matches[1]) . "\n";
print_r($simple_matches[1]);
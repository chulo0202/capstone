<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>FAMS Report</title></head>
<body>
<h2>FAMS Summary Report</h2>
<ul>
  <li>Farmers: {{ $farmers->count() }}</li>
  <li>Programs: {{ $programs->count() }}</li>
  <li>Distributions: {{ $distributions->count() }}</li>
</ul>
</body>
</html>

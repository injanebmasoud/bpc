<?php
function dmsToDecimal($deg, $min, $sec) {
    return floatval($deg) + floatval($min)/60 + floatval($sec)/3600;
}

$n = isset($_POST['n']) ? intval($_POST['n']) : 0;
$length = isset($_POST['length']) ? $_POST['length'] : [];
$alpha_deg = isset($_POST['alpha_deg']) ? $_POST['alpha_deg'] : [];
$alpha_min = isset($_POST['alpha_min']) ? $_POST['alpha_min'] : [];
$alpha_sec = isset($_POST['alpha_sec']) ? $_POST['alpha_sec'] : [];

$errors = [];
$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($n < 3) $errors[] = "تعداد نقاط باید حداقل ۳ باشد.";
    if (count($length) !== $n) $errors[] = "تعداد طول‌ها باید برابر n باشد.";
    if (count($alpha_deg) !== $n || count($alpha_min) !== $n || count($alpha_sec) !== $n) $errors[] = "تعداد زاویه‌ها باید برابر n باشد.";

    if (!$errors) {
        $alpha = [];
        for ($i=0; $i<$n; $i++) {
            $alpha[] = dmsToDecimal($alpha_deg[$i], $alpha_min[$i], $alpha_sec[$i]);
        }

        $sum_alpha_measured = array_sum($alpha);
        $sum_alpha_theoretical = ($n - 2) * 180;
        $angular_error = $sum_alpha_measured - $sum_alpha_theoretical;
        $alpha_adjusted = array_map(function($a) use ($angular_error, $n) {
            return $a - $angular_error / $n;
        }, $alpha);

        $theta = [0];
        for ($i=0; $i<$n; $i++) {
            $val = $theta[$i] + $alpha_adjusted[$i] - 180;
            $val = fmod(fmod($val, 360) + 360, 360);
            $theta[] = $val;
        }
        array_pop($theta);

        $deltaE = [];
        $deltaN = [];
        for ($i=0; $i<$n; $i++) {
            $rad = deg2rad($theta[$i]);
            $deltaE[] = $length[$i] * sin($rad);
            $deltaN[] = $length[$i] * cos($rad);
        }

        $x = [1000];
        $y = [1000];
        for ($i=0; $i<$n; $i++) {
            $x[] = $x[$i] + $deltaE[$i];
            $y[] = $y[$i] + $deltaN[$i];
        }
        array_pop($x);
        array_pop($y);

        $Fe = array_sum($deltaE);
        $Fn = array_sum($deltaN);
        $F = sqrt($Fe*$Fe + $Fn*$Fn);

        $totalLength = array_sum($length);
        $ratio = $F / $totalLength;
        if ($ratio < 1/10000) $accuracyClass = 1;
        else if ($ratio < 1/5000) $accuracyClass = 2;
        else if ($ratio < 1/2000) $accuracyClass = 3;
        else $accuracyClass = 4;

        $results = compact('x', 'y', 'Fe', 'Fn', 'F', 'accuracyClass', 'alpha_adjusted', 'theta');
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>پیمایش بسته - ورود درجه دقیقه ثانیه</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap');
body {
    font-family: 'Vazirmatn', Tahoma, sans-serif;
    background: #fff;
    color: #2c3e50;
    margin: 0;
    padding: 0;
    direction: rtl;
}
.container {
    max-width: 980px;
    margin: 30px auto 60px auto;
    background: #f7f9fb;
    border-radius: 10px;
    box-shadow: 0 10px 35px rgb(0 0 0 / 0.07);
    padding: 30px 40px;
}
h1 {
    font-weight: 700;
    font-size: 28px;
    margin-bottom: 25px;
    color: #34495e;
}
form label {
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
    font-size: 16px;
    color: #34495e;
}
input[type=number] {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #ced6e0;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}
input[type=number]:focus {
    border-color: #2980b9;
    outline: none;
}
button {
    background: #2980b9;
    color: white;
    border: none;
    font-weight: 600;
    font-size: 17px;
    padding: 13px 25px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 20px;
    transition: background 0.3s ease;
}
button:hover {
    background: #1c5980;
}
.error {
    background: #fce4e4;
    color: #c0392b;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    font-size: 15px;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 25px;
    font-size: 15px;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgb(0 0 0 / 0.05);
}
thead tr {
    background: #2980b9;
    color: white;
    font-weight: 600;
    text-align: center;
}
tbody tr:nth-child(odd) {
    background: #ecf0f1;
}
th, td {
    padding: 14px 12px;
    text-align: center;
}
@media (max-width: 600px) {
    .container {
        padding: 20px 15px;
    }
    th, td {
        padding: 10px 6px;
        font-size: 14px;
    }
    button {
        width: 100%;
    }
}
</style>
</head>
<body>
<div class="container">
<h1>پیمایش بسته - ورود درجه دقیقه ثانیه</h1>

<?php if ($errors): ?>
  <div class="error"><?= implode('<br>', $errors) ?></div>
<?php endif; ?>

<form method="post" novalidate>
  <label for="n">تعداد نقاط پیمایش (n):</label>
  <input type="number" id="n" name="n" value="<?= htmlspecialchars($n) ?>" min="3" max="20" required onchange="this.form.submit()">

  <?php if($n): ?>
  <table>
    <thead>
      <tr>
        <th>ردیف</th>
        <th>درجه (°)</th>
        <th>دقیقه (′)</th>
        <th>ثانیه (″)</th>
        <th>طول ضلع (m)</th>
      </tr>
    </thead>
    <tbody>
      <?php for ($i=0; $i<$n; $i++): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><input type="number" name="alpha_deg[]" value="<?= isset($alpha_deg[$i]) ? htmlspecialchars($alpha_deg[$i]) : '' ?>" min="0" max="359" step="1" required></td>
        <td><input type="number" name="alpha_min[]" value="<?= isset($alpha_min[$i]) ? htmlspecialchars($alpha_min[$i]) : '' ?>" min="0" max="59" step="1" required></td>
        <td><input type="number" name="alpha_sec[]" value="<?= isset($alpha_sec[$i]) ? htmlspecialchars($alpha_sec[$i]) : '' ?>" min="0" max="59.999" step="0.001" required></td>
        <td><input type="number" name="length[]" value="<?= isset($length[$i]) ? htmlspecialchars($length[$i]) : '' ?>" min="0" step="any" required></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <button type="submit">محاسبه</button>
  <?php endif; ?>
</form>

<?php if ($results): ?>
  <h2>نتایج پیمایش بسته</h2>
  <table>
    <thead>
      <tr><th>نقطه</th><th>x</th><th>y</th></tr>
    </thead>
    <tbody>
      <?php for($i=0; $i<$n; $i++): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= number_format($results['x'][$i], 3, '.', ',') ?></td>
        <td><?= number_format($results['y'][$i], 3, '.', ',') ?></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <p><strong>خطای بستن:</strong> F = <?= number_format($results['F'], 4) ?>, Fe = <?= number_format($results['Fe'], 4) ?>, Fn = <?= number_format($results['Fn'], 4) ?></p>
  <p><strong>درجه دقت پیمایش:</strong> <?= $results['accuracyClass'] ?></p>

  <h3>زاویه‌های تعدیل شده و ژیزمان‌ها</h3>
  <table>
    <thead>
      <tr><th>ردیف</th><th>α' (درجه اعشاری)</th><th>θ (ژیزمان)</th></tr>
    </thead>
    <tbody>
      <?php for($i=0; $i<$n; $i++): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= number_format($results['alpha_adjusted'][$i], 3) ?></td>
        <td><?= number_format($results['theta'][$i], 3) ?></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>
<?php endif; ?>

<p style="text-aline: center;">برنامه نویس: امیرمسعود ندیمی <a href="https://github.ir/injanebmasoud/bpc"> دریافت کد</a></p>

</div>
</body>
</html>

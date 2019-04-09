<?php
/**
 * [$host description]
 * @package PHPMailler/php
 * @author WebDev PMA #DoIT
 * @since  [<description>]
 */

class Mailer{
	function workedDays($from, $to) {
	    $workingDays = [1, 2, 3, 4, 5]; # date format = N (1 = Monday, ...)
	    $holidayDays = ['*-12-25', '*-01-01', '2013-12-23']; # variable and fixed holidays

	    $from = new DateTime($from);
	    $to = new DateTime($to);
	    $to->modify('+1 day');
	    $interval = new DateInterval('P1D');
	    $periods = new DatePeriod($from, $interval, $to);

	    $days = 0;
	    foreach ($periods as $period) {
	        if (!in_array($period->format('N'), $workingDays)) continue;
	        if (in_array($period->format('Y-m-d'), $holidayDays)) continue;
	        if (in_array($period->format('*-m-d'), $holidayDays)) continue;
	        $days++;
	    }
	    return $days;
	}
	function send($kd_sap2,$connection){
		require_once ('PHPMailer-master/PHPMailerAutoload.php');

		$mail             = new PHPMailer;
		$mail->isSMTP();
		$mail->Host       = 'mail.pinusmerahabadi.co.id';
		$mail->SMTPAuth   = true;
		$mail->Username   = 'sys_adm@pinusmerahabadi.co.id';
		$mail->Password   = 'sys0911';
		$mail->SMTPSecure = 'tls';
		$mail->Port       = 587;
		$mail->isHTML(true);
		
		$sql2 = "SELECT b.kd_sap2, a.no_do, a.tgl_sj, b.NM_DEPO AS city, CONCAT('PINUS MERAH ABADI, PT ',b.NM_DEPO) AS dist, a.tgl_sj+ INTERVAL a.ldp DAY + INTERVAL 2 DAY as InterValH3,
			c.email_bm send1, c.email_spv_log send2, c.email_sa send3,
			c.email_dim cc1, c.email_adim cc2, c.email_dps cc3, c.email_staff_scm_1 cc4, c.email_staff_scm_2 cc5, c.email_staff_scm_3 cc6, c.email_staff_scm_4 cc7, c.email_staff_scm_5 cc8
			FROM tb_upload_sj AS a
			LEFT JOIN rdepo b ON a.kd_sap2 = b.kd_sap2
			LEFT JOIN tb_email_user as c ON a.kd_sap2=c.kd_sap2
			WHERE status_sj = 'N'
			AND (tgl_sj + INTERVAL ldp DAY + INTERVAL 2 DAY) <= CURDATE() 
			AND b.kd_sap2='$kd_sap2'
			GROUP BY a.no_do";

		$statement2 = $connection->prepare($sql2);
		$statement2->execute();
		$result2    = $statement2->fetchAll();
		foreach ($result2 as $rows){
			$nama_depo = $rows['city'];
		}
		$mail->setFrom('sys_adm@pinusmerahabadi.co.id', 'PMA MAILER - PMA TERIMA BARANG');
		$MessageContent =
	       "<h2><font style=\"color:black; font-family:'Candara';\"><u></u></font></h2>
				<font style=\"color:black;  font-size:'16';  font-family:'Candara';\">
						Dear ".$nama_depo." ,<br><br>
						Mohon segera kirimkan SJ dan DTB jika barang sudah tiba. Terima kasih : <br><br></font>
							<table border=\"1\" cellpadding=\"10\" cellspacing=\"0\" style=\"  font:'Candara';\">
								<thead>
									<tr bgcolor=\"#F08080\">
									<font style=\"color: white;font-size:'16';  font-family:'Candara';\">
										<th>No DO</th>
										<th>Tanggal</th>
										<th>Depo</th>
										<th>Dist</th>
										<th>ETA</th>
										<th>Reminder</th>
										</font>
									</tr>
								</thead>
								<tbody>";
            foreach ($result2 as $rows){
				$dateNow = date("Y-m-d");
				$remind  = $this->workedDays($rows["InterValH3"],$dateNow);
             	$MessageContent .= 
	             	"<tr>
						<font style=\"color: black; font-size:'16';  font-family:'Candara';\">
							<td align =\"center\">".$rows["no_do"]."</td>
							<td align =\"left\">".$rows["tgl_sj"]."</td>
							<td align =\"left\">".$rows["city"]."</td>
							<td align =\"left\">".$rows["dist"]."</td>
							<td align =\"left\">".$rows["InterValH3"]."</td>
							<td align =\"left\">Ke- ".$remind."</td>
						</font>
	             	</tr>
	             	";
            	}
	        $MessageContent .= "</tbody>
			</table><br>
			<font style=\"color: black; font-size:'16';  font-family:'Candara';\">
			Dengan Link Berikut: http://pinusmerahabadi.co.id/pma_dev/pmaterimabarang/index.php/login<br><br>
			Terimakasih
				</font>";
		#gak yakin
		foreach ($result2 as $mails){
			$mail->addAddress($mails["send1"]);
			$mail->addAddress($mails["send2"]);
			$mail->addAddress($mails["send3"]);
			$mail->addCC($mails["cc1"]);
			$mail->addCC($mails["cc2"]);
			$mail->addCC($mails["cc3"]);
			$mail->addCC($mails["cc4"]);
			$mail->addCC($mails["cc5"]);
			$mail->addCC($mails["cc6"]);
			$mail->addCC($mails["cc7"]);
			$mail->addCC($mails["cc8"]);
		}

		$mail->Subject = "PMA TERIMA BARANG";
		$mail->Body    = $MessageContent;
		if (!$mail->Send()) {
			echo "Mailer Error: " . $mail->ErrorInfo ."<br>";
		} else {
			echo "Message has been sent <br>";
		}
	}
}

date_default_timezone_set('Asia/Jakarta');
// config DB_Server
$host       = "192.168.35.160";
$username   = "webdev";
$password   = "kunkka112";
$dbname     = "wredpine_dev";
$dsn        = "mysql:host=$host;dbname=$dbname";
$options    = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
$connection = new PDO($dsn, $username, $password, $options);

$query = "SELECT kd_sap2, tgl_sj + INTERVAL ldp DAY + INTERVAL 2 DAY AS InterValH3 
		FROM tb_upload_sj
		WHERE status_sj = 'N'
		AND (tgl_sj + INTERVAL ldp DAY + INTERVAL 2 DAY) < CURDATE()
		GROUP BY kd_sap2";

// statementExecute
$Stmnt = $connection->prepare($query);
$Stmnt->execute();
$result = $Stmnt->fetchAll();

foreach ($result as $row){
	$InterVal[] = $row["InterValH3"];
	$kd_sap2[] = $row["kd_sap2"];
}
echo "<pre>";
echo "====INTERVAL [tgl_sj + ldp + 2] AND status_sj = N====";
echo "<br>";

for ($i=0; $i < count($kd_sap2); $i++){
	$pmaMailer = new Mailer();
	$pmaMailer->send($kd_sap2[$i],$connection);
}

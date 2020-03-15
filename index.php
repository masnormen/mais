<?php /** @noinspection HtmlDeprecatedAttribute */

error_reporting(0);
$cookieJar = 'cache-siam.cache';

$input = file_get_contents('php://input');
$json = json_decode($input, true);

if (count($json) == 2) {
	$nim = $json['nim'];
	$password = $json['password'];
	
	$login = curl('https://siam.ub.ac.id/index.php', 'username=' . $nim . '&password=' . $password . '&login=Masuk');
	$rawdata = explode('<small class="error-code">', $login);
	
	if (!empty($rawdata[1])) {
		$hasil = trim(explode('</small>', $rawdata[1])[0]);
		if ($hasil == 'User belum terdaftar di database! Silahkan ulangi LOGIN beberapa saat lagi!<br>') {
			http_response_code(401);
			echo json_encode(array(
				'status' => 0,
				'message' => 'NIM not found',
			));
		} else {
			http_response_code(401);
			echo json_encode(array(
				'status' => 0,
				'message' => 'Incorrect password'
			));
		}
	} else {
		//log out from SIAM for security
		curl('https://siam.ub.ac.id/logout.php');
		//split to array
		$rawdata = explode('</div>', $rawdata[0]);
		//get image SIAM
		$image = $rawdata[2];
		preg_match('/background:url\((.*)\);/', $image, $image);
		//strip and trim html tags
		$rawdata = array_map(function ($x) {
			return trim(strip_tags($x));
		}, $rawdata);
		//get other biodata
		$nama = $rawdata[5];
		$nim = $rawdata[4];
		$fakstrat = explode('/', str_replace('Jenjang/Fakultas', '', $rawdata[6]));
		$strata = $fakstrat[0];
		$fakultas = $fakstrat[1];
		$jurusan = str_replace('Jurusan', '', $rawdata[7]);
		$prodi = str_replace('Program Studi', '', $rawdata[8]);
		$seleksi = substr($rawdata[9], 7);
		$no_ujian = str_replace('Nomor Ujian', '', $rawdata[10]);
		
		http_response_code(200);
		echo json_encode(array(
			'status' => 1,
			'data' => array(
				'nama' => $nama,
				'nim' => $nim,
				'strata' => $strata,
				'fakultas' => $fakultas,
				'jurusan' => $jurusan,
				'prodi' => $prodi,
				'seleksi' => $seleksi,
				'no_ujian' => $no_ujian,
				'image' => $image[1],
			)
		));
	}
} else {
	http_response_code(400);
	echo json_encode(array(
		'status' => 0,
		'message' => 'Incorrect parameter or method'
	));
}

//emptying cookie
file_put_contents($cookieJar, '');

function curl($url, $payload = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	if ($payload !== NULL) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	}
	curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookieJar']);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookieJar']);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i686 (x86_64); rv:2.0b4pre) Gecko/20100812 Minefield/4.0b4pre');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}
<?php require_once("vendor/autoload.php");

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

$input = json_decode(file_get_contents('php://input'), true);

// Check method, nim & password input
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	http_response_code(405);
	die(json_encode([
		'status' => 0,
		'message' => 'Method not allowed',
	]));
}
if (empty($input['nim']) || empty($input['password'])) {
	http_response_code(400);
	die(json_encode([
		'status' => 0,
		'message' => 'NIM or Password input is missing',
	]));
}

// Defining Guzzle default opts
$jar = new CookieJar();
$client = new Client([
	'base_uri' => 'https://bais.ub.ac.id/',
	'defaults' => [
		'headers' => [
			'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
			'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'
		],
		'cookies' => $jar,
		'connect_timeout' => 15,
		'verify' => false
	]
]);

// Retrieve challenge code
$res = $client->get('/', [
	'cookies' => $jar
]);
$body = $res->getBody();
libxml_use_internal_errors(true);
$dom = new DomDocument;
$dom->loadHTML($body);
$xpath = new DomXPath($dom);
$nodes = $xpath->query("//input[@id = 'challenge']/@value");
$challenge = $nodes->item(0)->textContent;

// Login to BAIS
$res = $client->post('/session/login/', [
	'allow_redirects' => [
		'referer' => true,
		'track_redirects' => true
	],
	'form_params' => [
		'userid' => $input['nim'],
		'password' => $input['password'],
		'challenge' => $challenge,
		'passport' => md5($challenge . $input['password']) . '_' . $input['nim']
	],
	'cookies' => $jar
]);

/********** START HTML PARSING **********/

$body = $res->getBody();
libxml_use_internal_errors(true);
$dom = new DomDocument;
$dom->loadHTML($body);
$xpath = new DomXPath($dom);

if ($xpath->query("//div[@id='errormsg']")->length > 0) {
	http_response_code(401);
	die(json_encode([
		'status' => 0,
		'message' => 'NIM or Password doesn\'t match or not registered',
	]));
}
else {
	try {
		$nim = $xpath->query("//th[text()='Kode']/following-sibling::td")->item(0)->nodeValue;
		$nama = $xpath->query("//th[text()='Nama']/following-sibling::td")->item(0)->nodeValue;
		$strata = $xpath->query("//th[text()='Jenjang']/following-sibling::td")->item(0)->nodeValue;
		$fakultas = $xpath->query("//th[text()='Fakultas']/following-sibling::td")->item(0)->nodeValue;
		$jurusan = $xpath->query("//th[text()='Jurusan']/following-sibling::td")->item(0)->nodeValue;
		$prodi = $xpath->query("//th[text()='Prodi']/following-sibling::td")->item(0)->nodeValue;
		$seleksi = $xpath->query("//th[text()='Seleksi']/following-sibling::td")->item(0)->nodeValue;
		$no_ujian = $xpath->query("//th[text()='Hint Answer']/following-sibling::td")->item(0)->nodeValue;
//		$image = "https://siakad.ub.ac.id/dirfoto/foto/foto_20" . substr($nim, 0, 2) . "/" . $nim . ".JPG";
		http_response_code(200);
		echo json_encode([
			'status' => 1,
			'data' => [
				'nama' => $nama,
				'nim' => $nim,
				'strata' => $strata,
				'fakultas' => $fakultas,
				'jurusan' => $jurusan,
				'prodi' => $prodi,
				'seleksi' => $seleksi,
				'no_ujian' => $no_ujian,
//				'image' => $image,
			],
		]);
	} catch (Exception $e) {
		http_response_code(503);
		echo json_encode([
			'status' => 0,
			'message' => 'SIAM data fetching is not available right now',
		]);
	}
}
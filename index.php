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
	'base_uri' => 'https://siam.ub.ac.id',
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

// Login to SIAM index.php
$client->post('/index.php', [
	'form_params' => [
		'username' => $input['nim'],
		'password' => $input['password'],
		'login' => 'Masuk'
	],
	'cookies' => $jar
]);

// Try to go to akademik.php
$res = $client->get('/akademik.php', [
	'allow_redirects' => [
		'referer' => true,
		'track_redirects' => true
	],
	'cookies' => $jar
]);

/********** START HTML PARSING **********/
$body = $res->getBody();
libxml_use_internal_errors(true);
$dom = new DomDocument;
$dom->loadHTML($body);
$xpath = new DomXPath($dom);

if (preg_match('/small class="error-code"/', $body)) {
	http_response_code(401);
	die(json_encode([
		'status' => 0,
		'message' => 'NIM or Password doesn\'t match or not registered',
	]));
} else {
	try {
		// If user is redirected to kuisioner.php or notifikasi.php
		if (!empty($res->getHeaderLine('X-Guzzle-Redirect-History'))) {
			$nodes = $xpath->query("//td[@class='text']");
			$image = null;
			$output = preg_replace('/\s{2,}/', '$', $nodes->item(0)->textContent);
			preg_match_all('/[\d]+|(\w+\s?)*\w+/', $output, $output);
			$output = $output[0];
			$nim = $output[0];
			$nama = $output[1];
			$strata = $output[4];
			$fakultas = $output[5];
			$jurusan = $output[7];
			$prodi = $output[9];
			$seleksi = $output[11];
			$no_ujian = $output[13];
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
					'image' => $image[1],
				],
//				'key' => $jwt
			]);
		} else { // If user can get to akademik.php
			$nodes = $xpath->query("//div[@class='bio-info']/div");
			$image = $xpath->query("//div[@class='photo-id']")->item(0)->attributes->getNamedItem("style")->nodeValue;
			preg_match('/background:url\((.*)\);/', $image, $image);
			$nim = $nodes->item(0)->nodeValue;
			$nama = $nodes->item(1)->nodeValue;
			$fakstrat = explode('/', str_replace('Jenjang/Fakultas', '', $nodes->item(2)->nodeValue));
			$strata = $fakstrat[0];
			$fakultas = $fakstrat[1];
			$jurusan = str_replace('Jurusan', '', $nodes->item(3)->nodeValue);
			$prodi = str_replace('Program Studi', '', $nodes->item(4)->nodeValue);
			$seleksi = substr($nodes->item(5)->nodeValue, 7);
			$no_ujian = str_replace('Nomor Ujian', '', $nodes->item(6)->nodeValue);
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
					'image' => $image[1],
				],
			]);
			
		}
	} catch (Exception $e) {
		http_response_code(503);
		echo json_encode([
			'status' => 0,
			'message' => 'SIAM data fetching is not available right now',
		]);
	}
}
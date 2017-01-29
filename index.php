<?php

// bot token here

$api = "botTOKEN";
$username = "";
$password = "";
$database = "";

// connection db

$DB = mysqli_connect("localhost", $username, $password, $database);

if (mysqli_connect_errno()) {
    exit();
}
require 'class-http-request.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);
$chatID = $update["message"]["chat"]["id"];
$msg = $update["message"]["text"];
$nome = $update["message"]["from"]["first_name"];
$userID = $update["message"]["from"]["id"];
$username = $update["message"]["from"]["username"];
$inline = $update["inline_query"]["id"];

if ($inline)
{
	$msg = $update["inline_query"]["query"];
	$userID = $update["inline_query"]["from"]["id"];
	$username = $update["inline_query"]["from"]["username"];
	$nome = $update["inline_query"]["from"]["first_name"];
}

if ($update["callback_query"])
{
	$id = $update["callback_query"]["id"];
	$data = $update["callback_query"]["data"];
	$mid = $update["callback_query"]["inline_message_id"];
	$userID = $update["callback_query"]["from"]["id"];
	$username = $update["callback_query"]["from"]["username"];
	$nome = $update["callback_query"]["from"]["first_name"];
	$iscb = true;
}

if ($update[chosen_inline_result][inline_message_id])
{
	$inl = $update["chosen_inline_result"]["inline_message_id"];
	$query = $update["chosen_inline_result"]["query"];
	$arr = explode(" ", $query, 2);
	$time = $arr[0];
	if (is_numeric($time)) exit;
	$userID = $update["chosen_inline_result"]["from"]["id"];
	$inl = '"' . mysqli_real_escape_string($DB, $inl) . '"';
	mysqli_query($DB, "insert into countdown (id, user_id, avviato, terminato, secondi) values ($inl, $userID, 0, 0, $time)");
}

if ($data)
{
	$arr = explode(" ", $data, 3);
	$rido = $arr[0];
	$time = $arr[1];
	$motiv = $arr[2];
	$args = array(
		'callback_query_id' => $id,
		'text' => "CountDown Avviato",
		'show_alert' => false
	);
	$r = new HttpRequest("post", "https://api.telegram.org/$api/answerCallbackQuery", $args);
	$rm = array(
		'inline_keyboard' => $nmenu
	);
	$rm = json_encode($rm);
	$n = $time;
	$con = mysqli_query($DB, "select id from countdown where id = '$mid' and avviato = 1");
	if (mysqli_num_rows($DB, $con))
	{
		exit;
	}
	else
	{
		mysqli_query($DB, "update countdown set avviato = 1 where id = '$mid'");
	}

	$k = $n;
	while ($k != 0)
	{
		$val = false;
		if ($n - $k < 10 and $k > 15) $s = 2;
		elseif ($n - $k < 30 and $k > 60) $s = 10;
		elseif ($k > 600)
		{
			$val = true;
			$s = 30 + $k % 30;
		}
		elseif ($k > 300)
		{
			$val = true;
			$s = 20 + $k % 20;
		}
		elseif ($k > 60) $s = 10 + $k % 10;
		elseif ($k > 10) $s = 5;
		elseif ($k > 6) $s = 2;
		else $s = 1;
		if ($rido)
		{
			$textcic = "  $motiv tra " . rand(1, $n) . " secondi";
		}else{
			$textcic = "  $motiv tra $k secondi";
		}
		if ($val)
		{
			$textcic .= "\n\n⚠️ Anche se non sembra, il Countdown è ancora attivo.";
		}

		$args = array(
			'inline_message_id' => $mid,
			'text' => $textcic,
			'parse_mode' => 'HTML',
		);

		$r = new HttpRequest("post", "https://api.telegram.org/$api/editMessageText", $args);
		$test = $r->getResponse();
		$json = json_decode($test, true);
		$error = " " . $json['description'];
		if (stripos($error, "MESSAGE_ID_INVALID") or $k < 0)
		{
			mysqli_query($DB, "update countdown set terminato = 1 where id = '$mid'");
			exit;
		}

		$k = $k - $s;
		sleep($s);
	}

	mysqli_query($DB, "update countdown set terminato = 1 where id = '$mid'");
	$textcic = $motiv;
	$args = array(
		'inline_message_id' => $mid,
		'text' => $textcic,
		'parse_mode' => 'HTML',
	);

	$r = new HttpRequest("post", "https://api.telegram.org/$api/editMessageText", $args);
}

if ($inline)
{
	$ar = explode(" ", $msg, 2);
	$time = $ar[0];
	$motiv = $ar[1];
	if (is_numeric($time) and $motiv)
	{
		$text = "Countdown di $time secondi creato con successo."
		$textrido = "Countdown Random creato con successo.";
		if ($time >= 2000)
		{
			$time = 2000;
			$text .= "\n\nTempo ridotto a 2000 secondi.";
		}

		$rmf = array(
			array(
				array(
					"text" => "Start",
					"callback_data" => "0 $time $motiv"
				)
			) ,
		);
		$rm = array(
			'inline_keyboard' => $rmf
		);
		$json[] = array(
			'type' => 'article',
			'id' => 'avvia_normale',
			'title' => "Countdown $time secondi",
			'description' => "Premi qui per inviare il Countdown: $motiv",
			'message_text' => $text,
			'parse_mode' => 'HTML',
			'reply_markup' => $rm,
		);
		$rmf = array(
			array(
				array(
					"text" => "Start",
					"callback_data" => "1 $time $motiv"
				)
			) ,
		);
		$rm = array(
			'inline_keyboard' => $rmf
		);
		$json[] = array(
			'type' => 'article',
			'id' => 'avvia_random',
			'title' => "Countdown Random $time sec.",
			'description' => "Premi qui per inviare il Countdown Random: $motiv",
			'message_text' => $textrido,
			'parse_mode' => 'HTML',
			'reply_markup' => $rm,
		);
	}
	elseif (!$motiv)
	{
		$json = array(
			array(
				'type' => 'article',
				'id' => 'input_errato',
				'title' => "INPUT ERRATO",
				'description' => "Messaggio di Countdown NON inserito",
				'message_text' => "Messaggio di Countdown <b>NON inserito</b>",
				'parse_mode' => 'HTML',
			)
		);
	}
	else
	{
		$json = array(
			array(
				'type' => 'article',
				'id' => 'input_errato',
				'title' => "INPUT ERRATO",
				'description' => "Numero di secondi del Countdown NON validi",
				'message_text' => "Numero di secondi del Countdown <b>NON validi</b>",
				'parse_mode' => 'HTML',
			)
		);
	}

	$json = json_encode($json);
	$args = array(
		'inline_query_id' => $inline,
		'results' => $json,
		'cache_time' => 100,
	);
	$r = new HttpRequest("post", "https://api.telegram.org/$api/answerInlineQuery", $args);
}

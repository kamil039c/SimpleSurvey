<?php
// src/Utils/SurveyTemplates.php
namespace App\Utils;

class SurveyTemplates {
	public static $questions = [
		[
			'key' => "ankietaImie", 
			'pytanie' => "Podaj swoje imie", 
			'field' => "name", 
			'isint' => false
		],[
			'key' => "ankietaNazwisko", 
			'pytanie' => "Podaj swoje nazwisko", 
			'field' => "surname", 
			'isint' => false
		],[
			'key' => "ankietaWiek", 
			'pytanie' => "Podaj swój wiek", 
			'field' => "age", 
			'isint' => true
		]
	];
}
?>
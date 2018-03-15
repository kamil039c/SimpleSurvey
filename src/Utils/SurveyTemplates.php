<?php
// src/Utils/SurveyTemplates.php
namespace App\Utils;

class SurveyTemplates {
	public static $questions = [
		[
			'klucz' => "ankietaImie", 
			'pytanie' => "Podaj swoje imie", 
			'field' => "name", 
			'isint' => false
		],[
			'klucz' => "ankietaNazwisko", 
			'pytanie' => "Podaj swoje nazwisko", 
			'field' => "surname", 
			'isint' => false
		],[
			'klucz' => "ankietaWiek", 
			'pytanie' => "Podaj swój wiek", 
			'field' => "age", 
			'isint' => true
		]
	];
}
?>
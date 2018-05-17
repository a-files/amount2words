<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr Kushnirov
 * Date: 18.05.2018
 * Time: 01:26
 */

class AMOUNT2WORDS {
  public $local = [];
  
  /**
   * Обработчик
   *
   * @param integer|float|string $number Число
   * @param string $lang Язык ответа ua|ru
   * @param string $currency Валюта UAH|USD|EUR|RUB
   * @param bool $decimal Добавить копейки
   * @param bool $decimal2String Преобразовывать копейки в строку
   * @param integer $textTransform Регистр текста 0 LOWER|1 UPPER|2 FIRST_CHAR_UPPER
   * @param string $codePage Кодировка ответа UTF-8|WINDOWS-1251
   *
   * @return string
   */
  public function getString($number = 0, $lang = 'ua', $currency = 'UAH', $decimal = true, $decimal2String = false, $textTransform = 0, $codePage = 'UTF-8') {
    
    $string = '';
    
    # Проверка типа числа, языка, валюты
    if (!in_array(gettype($number), [
        'integer',
        'double',
        'string'
      ], true)
      || 'string' !== gettype($lang)
      || !in_array(mb_strtolower($lang), [
        'ua',
        'ru'
      ], true)
      || 'string' !== gettype($currency)
      || !in_array(mb_strtoupper($currency), [
        'UAH',
        'USD',
        'EUR',
        'RUB'
      ], true)
      || 'string' !== gettype($codePage)
      || !in_array(mb_strtoupper($codePage), [
        'UTF-8',
        'WINDOWS-1251'
      ], true)) {
      
      return $string;
    }
    
    # Приведение типа к bool
    $decimal = !empty($decimal);
    $decimal2String = !empty($decimal2String);
    
    # Преобразование числа
    $numberValue = abs('string' === gettype($number) ? str_replace(",", ".", str_replace(' ', '', $number)) : (float)$number);
    
    # Преобразование разрядов
    $numberValue = (string)(empty($decimal) ? (integer)$numberValue : number_format($numberValue, 2, '.', ''));
    
    # Массив языковых локализаций
    $this->local = self::getLocalization($lang);
    
    # Проверка наличия локализаций
    if (empty($this->local)) {
      
      return $string;
    }
    
    # Объявление переменных
    $amount = $numberValue;
    $cents = 0;
    
    # Разделение целой и дробной части
    if ($decimal) {
      $tempArray = explode(".", $numberValue);
      
      $amount = $tempArray[0];
      $cents = $tempArray[1];
      
      unset($tempArray);
    }
    
    # Целая часть
    $string = 0 === (integer)$amount ? $this->local['digital'][$currency][0][0] : self::getNumber2String($amount, $currency, false);
    
    # Название валюты
    $string .= empty($string) ? '' : " ".self::getWordInCase($amount, $this->local['currency'][$currency]);
    
    # Дробная часть
    $string .= $decimal ? ($decimal2String ? " ".(0 === (integer)$cents ? $this->local['digital'][$currency][0][0] : self::getNumber2String("0{$cents}", $currency, true))." ".self::getWordInCase($cents, $this->local['cents'][$currency]) : " {$cents} ".self::getWordInCase($cents, $this->local['cents'][$currency])) : '';
    
    # Регистр
    switch ($textTransform) {
      # Верхний
      case 1 :
        $string = mb_convert_case($string, MB_CASE_UPPER);
        
        break;
      
      # Заглавная первая буква
      case 2 :
        $string = mb_strtoupper(mb_substr($string, 0, 1)).mb_substr($string, 1);
    }
    
    return empty($string) || 'UTF-8' === $codePage ? $string : mb_convert_encoding($string, $codePage, 'UTF-8');
  }
  /**
   * Получить число прописью
   *
   * @param string $number
   * @param string $currency
   * @param string $cents
   *
   * @return string
   */
  private function getNumber2String($number, $currency, $cents) {
    
    $result = [];
    
    # Разбиваем строку на триады
    foreach (array_reverse(str_split(str_pad($number, ceil(strlen($number) / 3) * 3, '0', STR_PAD_LEFT), 3)) as $triads => $value) {
      
      $result[$triads] = [];
      
      # Формируем строку прописью
      foreach ($arrayAmount = str_split($value) as $position => $char)
        
        if (empty($char)) {
          
          continue;
        }
        else
          switch ($position) {
            # Единицы
            case 0:
              $result[$triads][] = $this->local['digital'][$currency][4][$char];
              
              break;
            # Десятки
            case 1:
              if (1 === (integer)$char) {
                $result[$triads][] = $this->local['digital'][$currency][2][$arrayAmount[2]];
                
                break 2;
              }
              else {
                $result[$triads][] = $this->local['digital'][$currency][3][$char];
              }
              
              break;
            # Сотни
            case 2:
              $result[$triads][] = $this->local['digital'][$currency][(('UAH' === $currency && $cents) || ('UAH' === $currency ? 2 > $triads : 1 === $triads)) && 2 >= (integer)$char ? 5 : 1][$char];
              
              break;
          }
      
      $value *= 1;
      
      if (!$this->local['triads'][$triads]) {
        $this->local['triads'][$triads] = reset($this->local['triads']);
      }
      
      # Название разрядов
      if ($value && $triads)
        switch (true) {
          case preg_match("/^[1]$|^\\d*[0,2-9][1]$/", $value):
            $result[$triads][] = $this->local['triads'][$triads][0].$this->local['triads'][$triads][1];
            
            break;
          
          case preg_match("/^[2-4]$|\\d*[0,2-9][2-4]$/", $value):
            $result[$triads][] = $this->local['triads'][$triads][0].$this->local['triads'][$triads][2];
            
            break;
          
          default:
            $result[$triads][] = $this->local['triads'][$triads][0].$this->local['triads'][$triads][3];
            
            break;
        }
      
      $result[$triads] = implode(' ', $result[$triads]);
    }
    
    return empty($result) ? '' : implode(' ', array_reverse($result));
  }
  /**
   * Падеж от числа
   *
   * @param integer $number Число
   * @param array $arrayWords Массив слов
   *
   * @return string
   */
  private function getWordInCase($number, $arrayWords) {
    
    $number = $number % 100;
    $number = 19 < $number ? $number % 10 : $number;
    
    switch ($number) {
      case 1:
        
        return empty($arrayWords[1]) ? '' : (string)$arrayWords[1];
      
      case 2:
      case 3:
      case 4:
        
        return empty($arrayWords[2]) ? '' : (string)$arrayWords[2];
      
      default:
        
        return empty($arrayWords[0]) ? '' : (string)$arrayWords[0];
    }
  }
  /**
   * Языковая локализация
   *
   * @param string $lang
   *
   * @return bool|array
   */
  private function getLocalization($lang) {
    
    $array = [
      "ua" => [
        "currency" => [
          "UAH" => [
            # > 4
            "гривень",
            # 1
            "гривня",
            # 2, 3, 4
            "гривні"
          
          ],
          "USD" => [
            # > 4
            "доларів США",
            # 1
            "долар США",
            # 2, 3, 4
            "долари США"
          ],
          "EUR" => [
            # > 4
            "євро",
            # 1
            "євро",
            # 2, 3, 4
            "євро"
          ],
          "RUB" => [
            # > 4
            "рублів",
            # 1
            "рубль",
            # 2, 3, 4
            "рублі"
          ]
        ],
        "cents" => [
          "UAH" => [
            # > 4
            "копійок",
            # 1
            "копійка",
            # 2, 3, 4
            "копійки"
          
          ],
          "USD" => [
            # > 4
            "центів",
            # 1
            "цент",
            # 2, 3, 4
            "центи"
          ],
          "EUR" => [
            # > 4
            "центів",
            # 1
            "цент",
            # 2, 3, 4
            "центи"
          ],
          "RUB" => [
            # > 4
            "копійок",
            # 1
            "копійка",
            # 2, 3, 4
            "копійки"
          ]
        ],
        "digital" => [
          "UAH" => [
            ["нуль"],
            [
              "",
              "один",
              "два",
              "три",
              "чотири",
              "п'ять",
              "шість",
              "сім",
              "вісім",
              "дев'ять"
            ],
            [
              "десять",
              "одинадцять",
              "дванадцять",
              "тринадцять",
              "чотирнадцять",
              "п'ятнадцять",
              "шістнадцять",
              "сімнадцять",
              "вісімнадцять",
              "дев'ятнадцять"
            ],
            [
              "",
              "",
              "двадцять",
              "тридцять",
              "сорок",
              "п'ятдесят",
              "шістдесят",
              "сімдесят",
              "вісімдесят",
              "дев'яносто"
            ],
            [
              "",
              "сто",
              "двісті",
              "триста",
              "чотириста",
              "п'ятсот",
              "шістсот",
              "сімсот",
              "вісімсот",
              "дев'ятсот"
            ],
            [
              "",
              "одна",
              "дві"
            ]
          ],
          "USD" => [
            ["нуль"],
            [
              "",
              "один",
              "два",
              "три",
              "чотири",
              "п'ять",
              "шість",
              "сім",
              "вісім",
              "дев'ять"
            ],
            [
              "десять",
              "одинадцять",
              "дванадцять",
              "тринадцять",
              "чотирнадцять",
              "п'ятнадцять",
              "шістнадцять",
              "сімнадцять",
              "вісімнадцять",
              "дев'ятнадцять"
            ],
            [
              "",
              "",
              "двадцять",
              "тридцять",
              "сорок",
              "п'ятдесят",
              "шістдесят",
              "сімдесят",
              "вісімдесят",
              "дев'яносто"
            ],
            [
              "",
              "сто",
              "двісті",
              "триста",
              "чотириста",
              "п'ятсот",
              "шістсот",
              "сімсот",
              "вісімсот",
              "дев'ятсот"
            ],
            [
              "",
              "одна",
              "дві"
            ]
          ],
          "EUR" => [
            ["нуль"],
            [
              "",
              "один",
              "два",
              "три",
              "чотири",
              "п'ять",
              "шість",
              "сім",
              "вісім",
              "дев'ять"
            ],
            [
              "десять",
              "одинадцять",
              "дванадцять",
              "тринадцять",
              "чотирнадцять",
              "п'ятнадцять",
              "шістнадцять",
              "сімнадцять",
              "вісімнадцять",
              "дев'ятнадцять"
            ],
            [
              "",
              "",
              "двадцять",
              "тридцять",
              "сорок",
              "п'ятдесят",
              "шістдесят",
              "сімдесят",
              "вісімдесят",
              "дев'яносто"
            ],
            [
              "",
              "сто",
              "двісті",
              "триста",
              "чотириста",
              "п'ятсот",
              "шістсот",
              "сімсот",
              "вісімсот",
              "дев'ятсот"
            ],
            [
              "",
              "одна",
              "дві"
            ]
          ],
          "RUB" => [
            ["нуль"],
            [
              "",
              "один",
              "два",
              "три",
              "чотири",
              "п'ять",
              "шість",
              "сім",
              "вісім",
              "дев'ять"
            ],
            [
              "десять",
              "одинадцять",
              "дванадцять",
              "тринадцять",
              "чотирнадцять",
              "п'ятнадцять",
              "шістнадцять",
              "сімнадцять",
              "вісімнадцять",
              "дев'ятнадцять"
            ],
            [
              "",
              "",
              "двадцять",
              "тридцять",
              "сорок",
              "п'ятдесят",
              "шістдесят",
              "сімдесят",
              "вісімдесят",
              "дев'яносто"
            ],
            [
              "",
              "сто",
              "двісті",
              "триста",
              "чотириста",
              "п'ятсот",
              "шістсот",
              "сімсот",
              "вісімсот",
              "дев'ятсот"
            ],
            [
              "",
              "одна",
              "дві"
            ]
          ],
        ],
        "triads" => [
          [
            "...льйон",
            "",
            "а",
            "ів"
          ],
          [
            "тисяч",
            "а",
            "і",
            ""
          ],
          [
            "мільйон",
            "",
            "и",
            "ів"
          ],
          [
            "мільйард",
            "",
            "и",
            "ів"
          ],
          [
            "трильйон",
            "",
            "и",
            "ів"
          ],
          [
            "квадрильйон",
            "",
            "и",
            "ів"
          ],
          [
            "квинтильйон",
            "",
            "и",
            "ів"
          ]
        ]
      ],
      "ru" => [
        "currency" => [
          "UAH" => [
            # > 4
            "гривен",
            # 1
            "гривна",
            # 2, 3, 4
            "гривен"
          
          ],
          "USD" => [
            # > 4
            "долларов США",
            # 1
            "доллар США",
            # 2, 3, 4
            "доллара США"
          ],
          "EUR" => [
            # > 4
            "евро",
            # 1
            "евро",
            # 2, 3, 4
            "евро"
          ],
          "RUB" => [
            # > 4
            "рублей",
            # 1
            "рубль",
            # 2, 3, 4
            "рубля"
          ]
        ],
        "cents" => [
          "UAH" => [
            # > 4
            "копеек",
            # 1
            "копейка",
            # 2, 3, 4
            "копейки"
          
          ],
          "USD" => [
            # > 4
            "центов",
            # 1
            "цент",
            # 2, 3, 4
            "цента"
          ],
          "EUR" => [
            # > 4
            "центов",
            # 1
            "цент",
            # 2, 3, 4
            "цента"
          ],
          "RUB" => [
            # > 4
            "копеек",
            # 1
            "копейка",
            # 2, 3, 4
            "копейки"
          ]
        ],
        "digital" => [
          "UAH" => [
            ["ноль"],
            [
              "",
              "один",
              "два",
              "три",
              "четыре",
              "пять",
              "шесть",
              "семь",
              "восемь",
              "девять"
            ],
            [
              "десять",
              "одиннадцать",
              "двенадцать",
              "тринадцать",
              "четырнадцать",
              "пятнадцать",
              "шестнадцать",
              "семнадцать",
              "восемнадцать",
              "девятнадцать"
            ],
            [
              "",
              "",
              "двадцать",
              "тридцать",
              "сорок",
              "пятьдесят",
              "шестьдесят",
              "семьдесят",
              "восемьдесят",
              "девяносто"
            ],
            [
              "",
              "сто",
              "двести",
              "триста",
              "четыреста",
              "пятьсот",
              "шестьсот",
              "семьсот",
              "восемьсот",
              "девятьсот"
            ],
            [
              "",
              "одна",
              "две"
            ]
          ],
          "USD" => [
            ["ноль"],
            [
              "",
              "один",
              "два",
              "три",
              "четыре",
              "пять",
              "шесть",
              "семь",
              "восемь",
              "девять"
            ],
            [
              "десять",
              "одиннадцать",
              "двенадцать",
              "тринадцать",
              "четырнадцать",
              "пятнадцать",
              "шестнадцать",
              "семнадцать",
              "восемнадцать",
              "девятнадцать"
            ],
            [
              "",
              "",
              "двадцать",
              "тридцать",
              "сорок",
              "пятьдесят",
              "шестьдесят",
              "семьдесят",
              "восемьдесят",
              "девяносто"
            ],
            [
              "",
              "сто",
              "двести",
              "триста",
              "четыреста",
              "пятьсот",
              "шестьсот",
              "семьсот",
              "восемьсот",
              "девятьсот"
            ],
            [
              "",
              "одна",
              "две"
            ]
          ],
          "EUR" => [
            ["ноль"],
            [
              "",
              "один",
              "два",
              "три",
              "четыре",
              "пять",
              "шесть",
              "семь",
              "восемь",
              "девять"
            ],
            [
              "десять",
              "одиннадцать",
              "двенадцать",
              "тринадцать",
              "четырнадцать",
              "пятнадцать",
              "шестнадцать",
              "семнадцать",
              "восемнадцать",
              "девятнадцать"
            ],
            [
              "",
              "",
              "двадцать",
              "тридцать",
              "сорок",
              "пятьдесят",
              "шестьдесят",
              "семьдесят",
              "восемьдесят",
              "девяносто"
            ],
            [
              "",
              "сто",
              "двести",
              "триста",
              "четыреста",
              "пятьсот",
              "шестьсот",
              "семьсот",
              "восемьсот",
              "девятьсот"
            ],
            [
              "",
              "одна",
              "две"
            ]
          ],
          "RUB" => [
            ["ноль"],
            [
              "",
              "один",
              "два",
              "три",
              "четыре",
              "пять",
              "шесть",
              "семь",
              "восемь",
              "девять"
            ],
            [
              "десять",
              "одиннадцать",
              "двенадцать",
              "тринадцать",
              "четырнадцать",
              "пятнадцать",
              "шестнадцать",
              "семнадцать",
              "восемнадцать",
              "девятнадцать"
            ],
            [
              "",
              "",
              "двадцать",
              "тридцать",
              "сорок",
              "пятьдесят",
              "шестьдесят",
              "семьдесят",
              "восемьдесят",
              "девяносто"
            ],
            [
              "",
              "сто",
              "двести",
              "триста",
              "четыреста",
              "пятьсот",
              "шестьсот",
              "семьсот",
              "восемьсот",
              "девятьсот"
            ],
            [
              "",
              "одна",
              "две"
            ]
          ]
        ],
        "triads" => [
          [
            "...ллион",
            "",
            "а",
            "ов"
          ],
          [
            "тысяч",
            "а",
            "и",
            ""
          ],
          [
            "миллион",
            "",
            "а",
            "ов"
          ],
          [
            "миллиард",
            "",
            "а",
            "ов"
          ],
          [
            "триллион",
            "",
            "а",
            "ов"
          ],
          [
            "квадриллион",
            "",
            "а",
            "ов"
          ],
          [
            "квинтиллион",
            "",
            "а",
            "ов"
          ]
        ]
      ]
    ];
    
    return empty($array[$lang]) ? false : $array[$lang];
  }
}

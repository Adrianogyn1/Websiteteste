<?php

enum TransasaoType: int {
    case Deposito = 0;
    case Retirada = 1;
    case Bonus = 2;
    case Aposta = 3;
    case AjusteSaldo = 4;
    case CashBack = 5;
}

enum GameDificuldade: int {
    case Pessimo =0;
    case Ruim =1;
    case Normal = 2;
    case Bom = 3;
    case Otimo = 4;
}

enum GameType: int {
    case Slot = 0;
    case CassinoAoVivo = 1;
    case Roleta = 2;
    case Cartas = 3;
    case Dados = 4;
    case GameShow = 5;
    case Esporte = 6;
    case Crash = 7;
}

?>
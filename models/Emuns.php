<?php

enum TransasaoType: int {
    case Deposito = 0;
    case Retirada;
    case Bonus;
    case Aposta;
    case AjusteSaldo;
    case CashBack;
}

enum GameDificuldade: int {
    case Pessimo;
    case Ruim;
    case Normal;
    case Bom;
    case Otimo;
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
"""Affichage des montants : séparateur de milliers (espace insécable), 1000 → « 1 000 GNF »."""

NBSP = " "


def number(value):
    return f"{int(value):,}".replace(",", NBSP)


def gnf(amount):
    return f"{number(amount)}{NBSP}GNF"

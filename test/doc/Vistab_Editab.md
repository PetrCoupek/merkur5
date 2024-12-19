## třída VisTab 

Třída VisTab slouží pro přístup a vizualizaci databázové entity. V nejjednoduším
případě je entita definována jako tabulka databáze.

```
$t= new Vistab(['table'=>'TABULKA']);

```

Pro složitější případy lze využít poled na data SQL příkazem select.

Zároveň může select představovat na připravené view v rámci databáze.
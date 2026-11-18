<?php
// Fontes de notícias (RSS) e palavras-chave do tema.
// Para adicionar uma fonte, copie uma linha de 'feeds'. Feeds que falharem são só ignorados.
return [
    'feeds' => [
        ['nome' => 'Agência Brasil', 'url' => 'https://agenciabrasil.ebc.com.br/feed/ultimasnoticias/feed.xml'],
        ['nome' => 'Agência Brasil', 'url' => 'https://agenciabrasil.ebc.com.br/rss/direitos-humanos/feed.xml'],
        ['nome' => 'Agência Brasil', 'url' => 'https://agenciabrasil.ebc.com.br/rss/educacao/feed.xml'],
    ],

    // Uma notícia só entra se o título ou o resumo tiver pelo menos uma destas palavras
    // (sem acento e sem diferenciar maiúsculas).
    'palavras_chave' => [
        'racismo', 'racista', 'racial', 'injuria racial', 'cotas raciais',
        'indigena', 'quilombola', 'afro-brasileir', 'consciencia negra', 'povos tradicionais',
        'xenofob', 'imigrante', 'refugiad', '10.639',
    ],

    'max_dias' => 90,        // ignora matérias mais antigas que isso
    'por_execucao' => 10,    // máximo de notícias novas gravadas por rodada
];

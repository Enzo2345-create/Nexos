<?php
// Fontes (RSS/Atom) e regras de filtro do tema "diversidade étnico-racial".
// 'tipo' => 'noticia' vai para a seção Notícias; 'tipo' => 'artigo' vai para a seção Artigos.
// Feeds que falharem (endereço errado, site fora do ar) são apenas ignorados.
return [
    'feeds' => [
        // ----- Notícias -----
        ['nome' => 'Agência Brasil', 'tipo' => 'noticia', 'url' => 'https://agenciabrasil.ebc.com.br/feed/ultimasnoticias/feed.xml'],
        ['nome' => 'Agência Brasil', 'tipo' => 'noticia', 'url' => 'https://agenciabrasil.ebc.com.br/rss/direitos-humanos/feed.xml'],
        ['nome' => 'Agência Brasil', 'tipo' => 'noticia', 'url' => 'https://agenciabrasil.ebc.com.br/rss/educacao/feed.xml'],
        ['nome' => 'Brasil de Fato', 'tipo' => 'noticia', 'url' => 'https://www.brasildefato.com.br/feed/'],
        ['nome' => 'Alma Preta',     'tipo' => 'noticia', 'url' => 'https://almapreta.com.br/feed/'],

        // ----- Artigos -----
        ['nome' => 'Geledés',  'tipo' => 'artigo', 'url' => 'https://www.geledes.org.br/feed/'],
        ['nome' => 'Politize', 'tipo' => 'artigo', 'url' => 'https://www.politize.com.br/feed/'],
    ],

    // --- Filtro (as palavras são comparadas sem acento e sem diferenciar maiúsculas) ---
    // Basta UM destes termos para a matéria entrar:
    'termos_fortes' => [
        'racismo', 'racista', 'antirracis', 'racial', 'raciais',
        'consciencia negra', 'movimento negro', 'populacao negra', 'mulheres negras', 'jovens negros',
        'genocidio negro', 'zumbi dos palmares', 'lei de cotas',
        'quilomb', 'afro-brasileir', 'afrobrasileir', 'afrodescend',
        'povo indigena', 'povos indigenas', 'terra indigena', 'terras indigenas',
        'xenofob', '10.639', '11.645',
    ],
    // Termos que sozinhos são ambíguos: só valem se aparecerem pelo menos N diferentes juntos.
    'termos_fracos' => [
        'indigena', 'negro', 'negra', 'imigrante', 'refugiad', 'migrante', 'cigano',
        'discriminacao', 'preconceito', 'diversidade', 'etnia', 'etnico',
        'escravidao', 'escravizad', 'africano', 'africana', 'afro',
    ],
    'minimo_termos_fracos' => 2,
    // Expressões que NÃO contam como termo fraco ("rio negro", "mercado negro"...).
    'ignorar' => [
        'mercado negro', 'buraco negro', 'black friday', 'sexta-feira negra', 'rio negro',
        'mar negro', 'lista negra', 'ouro negro', 'magia negra', 'humor negro', 'caixa-preta',
    ],

    // Idade máxima das matérias, em dias, por tipo.
    'max_dias' => ['noticia' => 60, 'artigo' => 365],
    // Máximo de itens novos gravados por rodada, por tipo.
    'por_execucao' => ['noticia' => 10, 'artigo' => 6],
    // Quando houver pelo menos N artigos externos, os artigos fixos da equipe (sem link) ficam
    // ocultos (continuam no banco). Use 0 para sempre mostrar os fixos junto com os externos.
    'ocultar_fixos_com_externos' => 3,
    // Para a visita não ficar esperando demais: para de baixar feeds depois desse tempo.
    'tempo_max_segundos' => 12,
];

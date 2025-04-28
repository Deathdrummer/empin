<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. By default, the client will time out after 30 seconds.
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),

	'image_files'         => [
		'file-SecP2YtZHu4ZVGNsZJyTdQ',
		'file-X5jo5Tw9Y4dYE8MHbJKcev',
		'file-3TH7YpJALtHxo4nUHmAtGa',
		'file-MjLvUwHBZioNtAGkNXpBZ7',
		'file-KNUiQwJLiNHcbjfuJm5jjB',
		'file-VUAGLsAQYMz4m2CFd2Xf42',
		'file-UvFemerdoH6HmYCzpDi1Ai',
		'file-35WRr6uBpSWYmHWhzo84F7',
		'file-SnyJf1KCzQR7KBmv6U6Man',
		'file-UCWBdrebJTnTtqzfcrXj9f',
		'file-CwCh88KmSDcc7WHvuAXFz7',
		'file-KqzapkfKwMrWSLNBgA9FeV',
		'file-1RgSqopZj5JCmHs1rWdJo4',
		'file-Jer8epcq3xZ99Azad9PoAm',
		'file-PzQsJ2o1tFBeHSSTCmpXHC',
		'file-WXxu2tPy7cqzN9H8cibc7z',
		'file-LF2t1VY6373RQpRDxALNJb',
		'file-6Gj2RDkfM67VCccJSYg9qQ',
		'file-XmhGp2feuNVAtzfzRf5q8Y',
		'file-MUDQYg9cbXTiK2YXRUZk56',
	],
	
	'instruction' => '',
	
	'assistant_id' => 'asst_7y8dYdaljx57SStzZ4ElBHOm'
];

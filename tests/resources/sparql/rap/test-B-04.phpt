return array(
    'name'              => 'ask-02.rq',
    'group'             => 'RAP Ask Test Cases',
    'query'             => 'PREFIX  xsd: <http://www.w3.org/2001/XMLSchema#>

    SELECT  ?x ?y
    WHERE
        { ?x ?y "value"^^xsd:string }'
);
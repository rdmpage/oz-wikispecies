# oz-wikispecies
Extract bibliographic data from Wikispecies

## Run glitch app locally

- Download app from glitch.com

- cd to directory

- npm install

- npm start server.js

## Triplestore

```
curl http://127.0.0.1:5984/oz-wikispecies/_design/author/_list/triples/nt > wikispecies.nt
```

```
curl http://130.209.46.63/blazegraph/sparql?context-uri=https://species.wikimedia.org -H 'Content-Type: text/rdf+n3' --data-binary '@wikispecies.nt'  --progress-bar | tee /dev/null
```
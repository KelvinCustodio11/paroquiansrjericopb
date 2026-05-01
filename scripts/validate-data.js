#!/usr/bin/env node
/**
 * scripts/validate-data.js
 *
 * Valida arquivos data/*.json contra schemas/*.schema.json.
 * Implementa subset pratico do JSON Schema (sem dependencias externas).
 *
 * Mapeamento data -> schema -> chave-raiz (array a iterar):
 *   eventos.json         -> evento.schema.json         -> eventos[]
 *   artigos.json         -> artigo.schema.json         -> artigos[]
 *   homilias.json        -> homilia.schema.json        -> homilias[]
 *   horarios-missa.json  -> horarios-missa.schema.json -> (objeto)
 *   agenda-pastoral.json -> agenda-pastoral.schema.json -> (objeto)
 *   ministerios.json     -> ministerio.schema.json     -> ministerios[]
 *   paroco.json          -> paroco.schema.json         -> (objeto)
 *
 * Saida:
 *   - 0 se todos validos
 *   - 1 se houver erro
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const DATA_DIR = path.join(ROOT, 'data');
const SCHEMA_DIR = path.join(ROOT, 'schemas');

// dataFile -> { schema: schemaFile, collection: chaveDoArrayOuNullParaObjeto }
const PLAN = {
    'eventos.json':         { schema: 'evento.schema.json',         collection: 'eventos' },
    'artigos.json':         { schema: 'artigo.schema.json',         collection: 'artigos' },
    'homilias.json':        { schema: 'homilia.schema.json',        collection: 'homilias' },
    'horarios-missa.json':  { schema: 'horarios-missa.schema.json', collection: null },
    'agenda-pastoral.json': { schema: 'agenda-pastoral.schema.json', collection: null },
    'ministerios.json':     { schema: 'ministerio.schema.json',     collection: 'ministerios' },
    'paroco.json':          { schema: 'paroco.schema.json',         collection: null },
};

const errors = [];

function typeOf(value) {
    if (value === null) return 'null';
    if (Array.isArray(value)) return 'array';
    if (Number.isInteger(value)) return 'integer';
    if (typeof value === 'number') return 'number';
    return typeof value;
}

function matchesType(value, schemaType) {
    if (!schemaType) return true;
    const types = Array.isArray(schemaType) ? schemaType : [schemaType];
    const t = typeOf(value);
    return types.some(s => {
        if (s === 'number') return t === 'number' || t === 'integer';
        return s === t;
    });
}

function validate(value, schema, pathStr) {
    if (!schema) return;

    if (schema.type && !matchesType(value, schema.type)) {
        errors.push(`${pathStr}: type esperado ${JSON.stringify(schema.type)}, recebido ${typeOf(value)}`);
        return;
    }

    if (schema.enum && !schema.enum.includes(value)) {
        errors.push(`${pathStr}: valor ${JSON.stringify(value)} fora do enum ${JSON.stringify(schema.enum)}`);
    }

    if (typeof value === 'string') {
        if (schema.minLength != null && value.length < schema.minLength) {
            errors.push(`${pathStr}: minLength=${schema.minLength}, recebido ${value.length}`);
        }
        if (schema.maxLength != null && value.length > schema.maxLength) {
            errors.push(`${pathStr}: maxLength=${schema.maxLength}, recebido ${value.length}`);
        }
        if (schema.pattern) {
            const re = new RegExp(schema.pattern);
            if (!re.test(value)) {
                errors.push(`${pathStr}: nao bate com pattern ${schema.pattern}`);
            }
        }
    }

    if (typeOf(value) === 'object' && schema.properties) {
        if (Array.isArray(schema.required)) {
            for (const req of schema.required) {
                if (!(req in value)) {
                    errors.push(`${pathStr}: campo obrigatorio "${req}" ausente`);
                }
            }
        }
        for (const [key, subSchema] of Object.entries(schema.properties)) {
            if (key in value) {
                validate(value[key], subSchema, `${pathStr}.${key}`);
            }
        }
    }

    if (Array.isArray(value) && schema.items) {
        value.forEach((item, i) => validate(item, schema.items, `${pathStr}[${i}]`));
    }
}

let total = 0;
for (const [dataFile, { schema, collection }] of Object.entries(PLAN)) {
    const dataPath = path.join(DATA_DIR, dataFile);
    const schemaPath = path.join(SCHEMA_DIR, schema);

    if (!fs.existsSync(dataPath)) {
        console.log(`- ${dataFile}: arquivo ausente (pulando)`);
        continue;
    }
    if (!fs.existsSync(schemaPath)) {
        errors.push(`schema ausente: ${schema}`);
        continue;
    }

    const data = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
    const schemaObj = JSON.parse(fs.readFileSync(schemaPath, 'utf8'));

    if (collection) {
        const arr = data[collection];
        if (!Array.isArray(arr)) {
            errors.push(`${dataFile}: chave "${collection}" deve ser array`);
            continue;
        }
        arr.forEach((item, i) => validate(item, schemaObj, `${dataFile}.${collection}[${i}]`));
        total += arr.length;
        console.log(`- ${dataFile}: ${arr.length} item(ns) validados contra ${schema}`);
    } else {
        validate(data, schemaObj, dataFile);
        total += 1;
        console.log(`- ${dataFile}: 1 objeto validado contra ${schema}`);
    }
}

if (errors.length > 0) {
    console.error(`\n${errors.length} erro(s) de validacao:`);
    errors.forEach(e => console.error(`  ✗ ${e}`));
    process.exit(1);
}

console.log(`\nOK — ${total} entrada(s) validas em ${Object.keys(PLAN).length} arquivo(s).`);

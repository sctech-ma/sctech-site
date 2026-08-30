import { build } from 'esbuild';
import { createHash } from 'node:crypto';
import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const outdir = path.join(root, 'public', 'assets', 'build');
await rm(outdir, { recursive: true, force: true });
await mkdir(outdir, { recursive: true });

const entries = [
  { key: 'site.css', input: 'resources/css/site.css', loader: 'css' },
  { key: 'admin.css', input: 'resources/css/admin.css', loader: 'css' },
  { key: 'site.js', input: 'resources/js/site.js', loader: 'js' },
];

async function entrySource(entry) {
  const file = path.join(root, entry.input);
  const source = await readFile(file, 'utf8');
  if (entry.key !== 'site.css') return source;

  const imports = [...source.matchAll(/@import\s+["'](.+?)["'];/g)];
  if (imports.length === 0) return source;
  const directory = path.dirname(file);
  const parts = [];
  for (const match of imports) {
    parts.push(await readFile(path.resolve(directory, match[1]), 'utf8'));
  }
  return parts.join('\n');
}

const manifest = {};
for (const entry of entries) {
  const source = await entrySource(entry);
  const result = await build({
    absWorkingDir: root,
    stdin: {
      contents: source,
      sourcefile: entry.input,
      loader: entry.loader,
    },
    bundle: true,
    minify: true,
    target: entry.loader === 'css' ? undefined : ['es2020'],
    write: false,
    legalComments: 'none',
    charset: 'utf8',
    plugins: [{
      name: 'public-root-assets',
      setup(context) {
        context.onResolve({ filter: /^\/assets\// }, (args) => ({ path: args.path, external: true }));
      },
    }],
  });
  const bytes = result.outputFiles[0].contents;
  const hash = createHash('sha256').update(bytes).digest('hex').slice(0, 12);
  const extension = entry.loader === 'css' ? 'css' : 'js';
  const stem = entry.key.replace(/\.[^.]+$/, '');
  const basename = `${stem}.${hash}.${extension}`;
  await writeFile(path.join(outdir, basename), bytes);
  manifest[entry.key] = `/assets/build/${basename}`;
}

await writeFile(path.join(outdir, 'manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
const sizes = await Promise.all(Object.values(manifest).map(async (url) => {
  const file = path.join(root, 'public', url.replace(/^\//, ''));
  return [url, (await readFile(file)).byteLength];
}));
process.stdout.write(`${JSON.stringify(Object.fromEntries(sizes), null, 2)}\n`);

<template>
  <div class="font-mono text-sm">
    <div v-if="header" class="mb-2 text-xs text-neutral-500">{{ header }}</div>
    <pre class="bg-neutral-900 text-neutral-100 p-3 rounded overflow-auto" v-if="lines.length">
<code>
<template v-for="(line, idx) in lines" :key="idx">
<span :class="cls(line.kind)">{{ prefix(line.kind) }}{{ line.text }}</span>
<br />
</template>
</code>
    </pre>
  </div>
  
</template>

<script setup lang="ts">
import { computed } from 'vue';

type Line = { kind: 'context' | 'add' | 'del'; text: string };

const props = defineProps<{ header?: string; changes: Array<{ field: string; from: any; to: any }> }>();

const lines = computed<Line[]>(() => {
  const result: Line[] = [];
  for (const c of props.changes) {
    const fromVal = stringify(c.from);
    const toVal = stringify(c.to);
    result.push({ kind: 'context', text: `@@ ${c.field} @@` });
    result.push({ kind: 'del', text: `- ${fromVal}` });
    result.push({ kind: 'add', text: `+ ${toVal}` });
    result.push({ kind: 'context', text: '' });
  }
  return result;
});

function stringify(v: any): string {
  if (typeof v === 'object') return JSON.stringify(v);
  return String(v ?? '');
}

function prefix(kind: Line['kind']): string {
  if (kind === 'add') return '+';
  if (kind === 'del') return '-';
  return ' ';
}

function cls(kind: Line['kind']): string {
  if (kind === 'add') return 'text-green-300';
  if (kind === 'del') return 'text-red-300';
  return 'text-neutral-300';
}
</script>



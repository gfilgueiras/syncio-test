<template>
  <div class="rounded-xl border-2 border-indigo-200 bg-gradient-to-br from-white to-indigo-50/30 shadow-lg overflow-hidden">
    <div v-if="header" class="px-4 py-2.5 text-sm font-medium text-indigo-900 border-b-2 border-indigo-200 bg-indigo-50/50">
      {{ header }}
    </div>
    <div class="grid grid-cols-2">
      <!-- Headers -->
      <div class="text-xs font-semibold uppercase tracking-wider text-rose-600 border-r-2 border-indigo-200 px-4 py-2.5 bg-rose-50/50">
        Before
      </div>
      <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 px-4 py-2.5 bg-emerald-50/50">
        After
      </div>
      
      <!-- Left (deletions) -->
      <div class="relative border-r-2 border-indigo-200">
        <div v-for="(row, i) in leftRows" :key="'l'+i" class="grid grid-cols-[52px_1fr] font-mono text-sm leading-relaxed">
          <div class="select-none text-right pr-3 py-1.5 text-rose-400 bg-rose-50/40 border-b border-rose-100">
            {{ i + 1 }}
          </div>
          <div :class="[
            'py-1.5 px-3 border-b whitespace-pre-wrap',
            row.kind === 'del' 
              ? 'bg-rose-100/60 text-rose-900 border-rose-200' 
              : 'text-slate-700 border-slate-100'
          ]">
            <span v-if="row.kind === 'del'" class="text-rose-500 font-bold mr-1">−</span>
            {{ row.text }}
          </div>
        </div>
      </div>
      
      <!-- Right (additions) -->
      <div class="relative">
        <div v-for="(row, i) in rightRows" :key="'r'+i" class="grid grid-cols-[52px_1fr] font-mono text-sm leading-relaxed">
          <div class="select-none text-right pr-3 py-1.5 text-emerald-400 bg-emerald-50/40 border-b border-emerald-100">
            {{ i + 1 }}
          </div>
          <div :class="[
            'py-1.5 px-3 border-b whitespace-pre-wrap',
            row.kind === 'add' 
              ? 'bg-emerald-100/60 text-emerald-900 border-emerald-200' 
              : 'text-slate-700 border-slate-100'
          ]">
            <span v-if="row.kind === 'add'" class="text-emerald-500 font-bold mr-1">+</span>
            {{ row.text }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

type Change = { field: string; from: any; to: any };
type Row = { text: string; kind: 'add' | 'del' | 'context'; prefix: string };

const props = defineProps<{ header?: string; changes: Change[] }>();

const leftRows = computed<Row[]>(() => {
  const rows: Row[] = [];
  for (const c of props.changes) {
    const lines = toLines(c.from);
    if (rows.length > 0) rows.push({ text: '', kind: 'context', prefix: '' });
    rows.push({ text: `@@ ${c.field} @@`, kind: 'context', prefix: '' });
    for (const ln of lines) rows.push({ text: ln, kind: 'del', prefix: '' });
  }
  return rows;
});

const rightRows = computed<Row[]>(() => {
  const rows: Row[] = [];
  for (const c of props.changes) {
    const lines = toLines(c.to);
    if (rows.length > 0) rows.push({ text: '', kind: 'context', prefix: '' });
    rows.push({ text: `@@ ${c.field} @@`, kind: 'context', prefix: '' });
    for (const ln of lines) rows.push({ text: ln, kind: 'add', prefix: '' });
  }
  return rows;
});

function toLines(v: any): string[] {
  const s = typeof v === 'string' ? v : JSON.stringify(v, null, 2);
  return s.split(/\r?\n/);
}
</script>

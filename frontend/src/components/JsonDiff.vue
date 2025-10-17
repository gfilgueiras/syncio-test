<template>
  <div class="rounded-xl border-2 border-indigo-200 bg-gradient-to-br from-white to-indigo-50/30 shadow-lg overflow-hidden">
    <!-- Header with sync option -->
    <div class="px-4 py-3 border-b-2 border-indigo-200 bg-indigo-50/50 flex items-center justify-between">
      <div class="text-sm font-medium text-indigo-900">
        {{ header }}
      </div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input 
          type="checkbox" 
          v-model="syncScroll" 
          class="w-4 h-4 text-indigo-600 rounded border-indigo-300 focus:ring-2 focus:ring-indigo-200"
        />
        <span class="text-xs font-medium text-slate-600">Sync scroll</span>
      </label>
    </div>

    <div class="grid grid-cols-2">
      <!-- Headers -->
      <div class="text-xs font-semibold uppercase tracking-wider text-rose-600 border-r-2 border-indigo-200 px-4 py-2.5 bg-rose-50/50">
        Before (Payload 1)
      </div>
      <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 px-4 py-2.5 bg-emerald-50/50">
        After (Payload 2)
      </div>
      
      <!-- Left (original) -->
      <div 
        ref="leftPane" 
        @scroll="onLeftScroll"
        class="relative border-r-2 border-indigo-200 overflow-auto max-h-[600px] bg-slate-50/30"
      >
        <div class="min-w-full">
          <div 
            v-for="(line, i) in leftLines" 
            :key="'l'+i"
            :class="[
              'flex items-start px-4 py-0.5 w-full',
              line.type === 'removed' || line.type === 'changed' ? 'bg-rose-100/70 text-rose-900' : 'text-slate-700',
              line.hidden ? 'hidden' : ''
            ]"
          >
            <span class="select-none inline-block w-10 text-right pr-3 text-slate-400 flex-shrink-0 font-mono text-sm">{{ i + 1 }}</span>
            
            <!-- Collapse button for blocks -->
            <button
              v-if="line.isBlockStart"
              @click="toggleBlock(i, 'left')"
              class="mr-1 flex-shrink-0 w-4 h-4 flex items-center justify-center text-slate-500 hover:text-slate-700 hover:bg-slate-200 rounded transition"
            >
              <svg v-if="!line.collapsed" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
              <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
            <span v-else class="w-4 flex-shrink-0"></span>

            <span class="font-mono text-sm whitespace-pre flex-1">
              <span v-if="line.type === 'removed' || line.type === 'changed'" class="text-rose-500 font-bold">− </span>
              <span v-if="line.collapsed">{{ line.collapsedText }}</span>
              <span v-else>{{ line.text }}</span>
            </span>
          </div>
        </div>
      </div>
      
      <!-- Right (modified) -->
      <div 
        ref="rightPane" 
        @scroll="onRightScroll"
        class="relative overflow-auto max-h-[600px] bg-slate-50/30"
      >
        <div class="min-w-full">
          <div 
            v-for="(line, i) in rightLines" 
            :key="'r'+i"
            :class="[
              'flex items-start px-4 py-0.5 w-full',
              line.type === 'added' || line.type === 'changed' ? 'bg-emerald-100/70 text-emerald-900' : 'text-slate-700',
              line.hidden ? 'hidden' : ''
            ]"
          >
            <span class="select-none inline-block w-10 text-right pr-3 text-slate-400 flex-shrink-0 font-mono text-sm">{{ i + 1 }}</span>
            
            <!-- Collapse button for blocks -->
            <button
              v-if="line.isBlockStart"
              @click="toggleBlock(i, 'right')"
              class="mr-1 flex-shrink-0 w-4 h-4 flex items-center justify-center text-slate-500 hover:text-slate-700 hover:bg-slate-200 rounded transition"
            >
              <svg v-if="!line.collapsed" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
              <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
            <span v-else class="w-4 flex-shrink-0"></span>

            <span class="font-mono text-sm whitespace-pre flex-1">
              <span v-if="line.type === 'added' || line.type === 'changed'" class="text-emerald-500 font-bold">+ </span>
              <span v-if="line.collapsed">{{ line.collapsedText }}</span>
              <span v-else>{{ line.text }}</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

type Line = { 
  text: string; 
  type: 'normal' | 'added' | 'removed' | 'changed'; 
  path?: string;
  isBlockStart?: boolean;
  isBlockEnd?: boolean;
  blockEndIndex?: number;
  indent?: number;
  collapsed?: boolean;
  collapsedText?: string;
  hidden?: boolean;
};

const props = defineProps<{ 
  header?: string; 
  before: any;
  after: any;
  changes: Array<{ field: string; from: any; to: any }>;
}>();

const syncScroll = ref(true);
const leftPane = ref<HTMLElement | null>(null);
const rightPane = ref<HTMLElement | null>(null);
const isScrolling = ref(false);

const leftLines = ref<Line[]>([]);
const rightLines = ref<Line[]>([]);

// Deep comparison to find all changed paths
function findAllChangedPaths(obj1: any, obj2: any, path = ''): Set<string> {
  const changed = new Set<string>();
  
  if (typeof obj1 !== 'object' || typeof obj2 !== 'object' || obj1 === null || obj2 === null) {
    if (obj1 !== obj2) {
      changed.add(path);
    }
    return changed;
  }

  const keys1 = Object.keys(obj1 || {});
  const keys2 = Object.keys(obj2 || {});
  const allKeys = new Set([...keys1, ...keys2]);

  for (const key of allKeys) {
    const newPath = path ? `${path}.${key}` : key;
    
    if (!(key in obj1)) {
      changed.add(newPath);
    } else if (!(key in obj2)) {
      changed.add(newPath);
    } else if (Array.isArray(obj1[key]) && Array.isArray(obj2[key])) {
      const maxLen = Math.max(obj1[key].length, obj2[key].length);
      for (let i = 0; i < maxLen; i++) {
        const arrPath = `${newPath}[${i}]`;
        if (i >= obj1[key].length || i >= obj2[key].length) {
          changed.add(arrPath);
        } else {
          const subChanges = findAllChangedPaths(obj1[key][i], obj2[key][i], arrPath);
          subChanges.forEach(p => changed.add(p));
        }
      }
    } else if (typeof obj1[key] === 'object' && typeof obj2[key] === 'object') {
      const subChanges = findAllChangedPaths(obj1[key], obj2[key], newPath);
      subChanges.forEach(p => changed.add(p));
    } else if (obj1[key] !== obj2[key]) {
      changed.add(newPath);
    }
  }

  return changed;
}

const changedPaths = computed(() => {
  return findAllChangedPaths(props.before, props.after);
});

// Convert object to formatted JSON lines with path tracking and block detection
function toJsonLinesWithPaths(obj: any, parentPath = ''): Line[] {
  const lines: Line[] = [];
  
  function traverse(o: any, indent: number, currentPath: string) {
    const spaces = '  '.repeat(indent);
    
    if (Array.isArray(o)) {
      const startIdx = lines.length;
      lines.push({ 
        text: spaces + '[', 
        path: currentPath,
        type: 'normal',
        isBlockStart: true,
        indent,
        collapsed: false
      });
      
      o.forEach((item, idx) => {
        traverse(item, indent + 1, `${currentPath}[${idx}]`);
        if (idx < o.length - 1) {
          lines[lines.length - 1].text += ',';
        }
      });
      
      lines.push({ 
        text: spaces + ']', 
        path: currentPath,
        type: 'normal',
        isBlockEnd: true,
        indent
      });
      
      const endIdx = lines.length - 1;
      lines[startIdx].blockEndIndex = endIdx;
      lines[startIdx].collapsedText = `${spaces}[...] // ${o.length} items`;
      
    } else if (typeof o === 'object' && o !== null) {
      const startIdx = lines.length;
      lines.push({ 
        text: spaces + '{', 
        path: currentPath,
        type: 'normal',
        isBlockStart: true,
        indent,
        collapsed: false
      });
      
      const keys = Object.keys(o);
      keys.forEach((key, idx) => {
        const keyPath = currentPath ? `${currentPath}.${key}` : key;
        const value = o[key];
        
        if (typeof value === 'object' && value !== null) {
          lines.push({ 
            text: `${spaces}  "${key}": `, 
            path: keyPath,
            type: 'normal'
          });
          traverse(value, indent + 1, keyPath);
          if (idx < keys.length - 1) {
            lines[lines.length - 1].text += ',';
          }
        } else {
          const jsonValue = JSON.stringify(value);
          const line = `${spaces}  "${key}": ${jsonValue}${idx < keys.length - 1 ? ',' : ''}`;
          lines.push({ 
            text: line, 
            path: keyPath,
            type: 'normal'
          });
        }
      });
      
      lines.push({ 
        text: spaces + '}', 
        path: currentPath,
        type: 'normal',
        isBlockEnd: true,
        indent
      });
      
      const endIdx = lines.length - 1;
      lines[startIdx].blockEndIndex = endIdx;
      lines[startIdx].collapsedText = `${spaces}{...} // ${keys.length} properties`;
      
    } else {
      lines.push({ 
        text: spaces + JSON.stringify(o), 
        path: currentPath,
        type: 'normal'
      });
    }
  }
  
  traverse(obj, 0, parentPath);
  return lines;
}

// Determine if a line is changed based on its path
function getLineType(linePath: string): Line['type'] {
  for (const changedPath of changedPaths.value) {
    if (linePath === changedPath || linePath.startsWith(changedPath + '.') || linePath.startsWith(changedPath + '[')) {
      return 'changed';
    }
  }
  return 'normal';
}

// Initialize lines
function initializeLines() {
  const linesWithPaths = toJsonLinesWithPaths(props.before);
  leftLines.value = linesWithPaths.map(line => ({
    ...line,
    type: getLineType(line.path || '')
  }));

  const rightLinesWithPaths = toJsonLinesWithPaths(props.after);
  rightLines.value = rightLinesWithPaths.map(line => ({
    ...line,
    type: getLineType(line.path || '')
  }));
}

initializeLines();

function toggleBlock(index: number, side: 'left' | 'right') {
  const lines = side === 'left' ? leftLines.value : rightLines.value;
  const line = lines[index];
  
  if (!line.isBlockStart || line.blockEndIndex === undefined) return;
  
  line.collapsed = !line.collapsed;
  
  // Hide/show lines between start and end
  for (let i = index + 1; i < line.blockEndIndex; i++) {
    lines[i].hidden = line.collapsed;
  }
}

function onLeftScroll() {
  if (!syncScroll.value || isScrolling.value) return;
  isScrolling.value = true;
  if (rightPane.value && leftPane.value) {
    rightPane.value.scrollTop = leftPane.value.scrollTop;
    rightPane.value.scrollLeft = leftPane.value.scrollLeft;
  }
  setTimeout(() => { isScrolling.value = false; }, 50);
}

function onRightScroll() {
  if (!syncScroll.value || isScrolling.value) return;
  isScrolling.value = true;
  if (leftPane.value && rightPane.value) {
    leftPane.value.scrollTop = rightPane.value.scrollTop;
    leftPane.value.scrollLeft = rightPane.value.scrollLeft;
  }
  setTimeout(() => { isScrolling.value = false; }, 50);
}
</script>

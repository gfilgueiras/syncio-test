<template>
  <main class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <div class="max-w-6xl mx-auto p-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
          Payload Comparison Tool
        </h1>
        <p class="text-slate-600">Compare two JSON payloads and visualize differences</p>
      </div>

      <!-- Controls Card -->
      <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-indigo-100">
        <div class="space-y-4">
          <!-- Payload 1 -->
          <div>
            <div class="flex items-center gap-3 mb-2">
              <label class="text-sm font-semibold text-slate-700">Payload 1 (JSON)</label>
              
              <!-- iOS-style toggle -->
              <button
                @click="showPayload1 = !showPayload1"
                :class="[
                  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                  showPayload1 ? 'bg-indigo-600' : 'bg-slate-300'
                ]"
              >
                <span
                  :class="[
                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                    showPayload1 ? 'translate-x-6' : 'translate-x-1'
                  ]"
                />
              </button>
            </div>
            
            <div v-show="showPayload1" class="space-y-2">
              <textarea 
                v-model="payload1Json" 
                rows="15"
                class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl bg-slate-50 text-slate-900 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 outline-none transition font-mono text-sm"
                placeholder="Paste or edit JSON here..."
              ></textarea>
              <p v-if="payload1Error" class="text-sm text-red-600">{{ payload1Error }}</p>
            </div>
          </div>

          <!-- Send Button -->
          <div class="flex items-center gap-4">
            <button 
              :disabled="sending || ackP1" 
              @click="sendFirst" 
              class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 active:scale-95 text-white font-semibold shadow-lg shadow-indigo-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
            >
              <span v-if="!sending && !ackP1">Send Payload</span>
              <span v-if="sending" class="flex items-center gap-2">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending...
              </span>
              <span v-if="ackP1">✓ Sent</span>
            </button>

            <!-- New Comparison Button (only shown after diff is done) -->
            <button 
              v-if="status === 'done'"
              @click="resetComparison" 
              class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 active:scale-95 text-white font-semibold shadow-lg shadow-emerald-200 transition-all duration-200 animate-fade-in"
            >
              <span class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                New Comparison
              </span>
            </button>
            
            <!-- Payload 1 Status -->
            <div v-if="ackP1 && status !== 'done'" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border-2 border-emerald-200 rounded-xl animate-fade-in">
              <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
              <span class="text-sm font-medium text-emerald-700">Payload 1 received</span>
            </div>
          </div>

          <!-- Payload 2 Status -->
          <div v-if="waitingSecond && !ackP2" class="flex items-center gap-3 px-4 py-3 bg-amber-50 border-2 border-amber-200 rounded-xl animate-fade-in">
            <div class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
            </div>
            <span class="text-sm font-medium text-amber-700">Scheduling Payload 2 in 30 seconds...</span>
          </div>

          <div v-if="ackP2" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border-2 border-emerald-200 rounded-xl animate-fade-in">
            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
            <span class="text-sm font-medium text-emerald-700">Payload 2 received</span>
          </div>

          <!-- Comparison Status -->
          <div v-if="status === 'pending'" class="flex items-center gap-3 px-4 py-3 bg-sky-50 border-2 border-sky-200 rounded-xl animate-fade-in">
            <svg class="animate-spin h-5 w-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-sky-700">Comparing payloads...</span>
          </div>

          <!-- Error -->
          <div v-if="error" class="flex items-center gap-2 px-4 py-3 bg-red-50 border-2 border-red-200 rounded-xl animate-fade-in">
            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium text-red-700">{{ error }}</span>
          </div>
        </div>
      </div>

      <!-- Results -->
      <div v-if="status === 'done'" class="space-y-6 animate-fade-in">
        <h2 class="text-2xl font-bold text-slate-800">Comparison Results</h2>
        
        <div v-if="!diffHasChanges" class="px-6 py-8 bg-white rounded-2xl border-2 border-slate-200 text-center">
          <div class="text-6xl mb-4">✨</div>
          <p class="text-lg font-medium text-slate-600">No differences detected</p>
        </div>

        <!-- Full Product Comparison -->
        <div class="space-y-3">
          <h3 class="text-lg font-semibold text-slate-700 flex items-center gap-2">
            <span class="w-1 h-6 bg-indigo-500 rounded-full"></span>
            Product Comparison
          </h3>
          <JsonDiff 
            header="Full JSON Payloads" 
            :before="payload1" 
            :after="payload2"
            :changes="allChanges"
          />
        </div>

        <!-- Images -->
        <div class="space-y-3">
          <h3 class="text-lg font-semibold text-slate-700 flex items-center gap-2">
            <span class="w-1 h-6 bg-purple-500 rounded-full"></span>
            Images
          </h3>
          <div class="grid grid-cols-3 gap-4 px-6 py-4 bg-white rounded-xl border border-indigo-100">
            <div class="text-center">
              <div class="text-2xl font-bold text-emerald-600">{{ diff.images?.added?.length || 0 }}</div>
              <div class="text-xs font-medium text-slate-500 uppercase">Added</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-rose-600">{{ diff.images?.removed?.length || 0 }}</div>
              <div class="text-xs font-medium text-slate-500 uppercase">Removed</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-indigo-600">{{ diff.images?.changed?.length || 0 }}</div>
              <div class="text-xs font-medium text-slate-500 uppercase">Modified</div>
            </div>
          </div>
          
          <div v-if="diff.images?.changed?.length" class="space-y-3">
            <div v-for="c in diff.images.changed" :key="c.id">
              <JsonDiff 
                :header="`Image #${c.id}`" 
                :before="findImageById(payload1.images, c.id)"
                :after="findImageById(payload2.images, c.id)"
                :changes="changesToArray(c.changes)" 
              />
            </div>
          </div>
        </div>

        <!-- Variants -->
        <div class="space-y-3">
          <h3 class="text-lg font-semibold text-slate-700 flex items-center gap-2">
            <span class="w-1 h-6 bg-pink-500 rounded-full"></span>
            Variants
          </h3>
          <div class="grid grid-cols-3 gap-4 px-6 py-4 bg-white rounded-xl border border-indigo-100">
            <div class="text-center">
              <div class="text-2xl font-bold text-emerald-600">{{ diff.variants?.added?.length || 0 }}</div>
              <div class="text-xs font-medium text-slate-500 uppercase">Added</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-rose-600">{{ diff.variants?.removed?.length || 0 }}</div>
              <div class="text-xs font-medium text-slate-500 uppercase">Removed</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-indigo-600">{{ diff.variants?.changed?.length || 0 }}</div>
              <div class="text-xs font-medium text-slate-500 uppercase">Modified</div>
            </div>
          </div>
          
          <div v-if="diff.variants?.changed?.length" class="space-y-3">
            <div v-for="c in diff.variants.changed" :key="c.id">
              <JsonDiff 
                :header="`Variant #${c.id}`" 
                :before="findVariantById(payload1.variants, c.id)"
                :after="findVariantById(payload2.variants, c.id)"
                :changes="changesToArray(c.changes)" 
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, ref } from 'vue';
import JsonDiff from './components/JsonDiff.vue';

const ackP1 = ref(false);
const ackP2 = ref(false);
const sending = ref(false);
const waitingSecond = ref(false);
const status = ref<'idle' | 'pending' | 'done'>('idle');
const diff = ref<any>({});
const error = ref<string | null>(null);
const payload1Error = ref<string | null>(null);
const showPayload1 = ref(false);

const diffHasChanges = computed(() => {
  const d = diff.value || {};
  const hasRoot = d.rootChanges && Object.keys(d.rootChanges).length > 0;
  const hasImages = d.images && ((d.images.added?.length || 0) + (d.images.removed?.length || 0) + (d.images.changed?.length || 0) > 0);
  const hasVariants = d.variants && ((d.variants.added?.length || 0) + (d.variants.removed?.length || 0) + (d.variants.changed?.length || 0) > 0);
  return hasRoot || hasImages || hasVariants;
});

const defaultPayload1 = {
  id: 432232523,
  title: 'Syncio T-Shirt',
  description: '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aen',
  images: [
    { id: 26372, position: 1, url: 'https://cu.syncio.co/images/random_image_1.png' },
    { id: 23445, position: 2, url: 'https://cu.syncio.co/images/random_image_2.png' },
    { id: 34566, position: 3, url: 'https://cu.syncio.co/images/random_image_3.png' },
    { id: 33253, position: 4, url: 'https://cu.syncio.co/images/random_image_4.png' },
    { id: 56353, position: 5, url: 'https://cu.syncio.co/images/random_image_5.png' }
  ],
  variants: [
    { id: 433232, sku: 'SKU-II-10', barcode: 'BAR_CODE_230', image_id: 26372, inventory_quantity: 12 },
    { id: 231544, sku: 'SKU-II-20', barcode: 'B231342313', image_id: 23445, inventory_quantity: 2 },
    { id: 323245, sku: 'SKU-II-1O', barcode: 'BACDSDE_0', image_id: 34566, inventory_quantity: 8 },
    { id: 323445, sku: 'SKU-II-1o', barcode: 'AR_CO23DE_23', image_id: 33253, inventory_quantity: 0 }
  ]
};

const payload1Json = ref(JSON.stringify(defaultPayload1, null, 2));
const payload1 = computed(() => {
  try {
    return JSON.parse(payload1Json.value);
  } catch {
    return defaultPayload1;
  }
});

const payload2 = {
  id: 432232523,
  title: 'Syncio T-Shirt',
  description: '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aen',
  images: [
    { id: 26372, position: 1, url: 'https://cu.syncio.co/images/random_image_1.png' },
    { id: 33245, position: 2, url: 'https://cu.syncio.co/images/random_image_2.png' },
    { id: 56353, position: 3, url: 'https://cu.syncio.co/images/random_image_5.png' },
    { id: 33213, position: 4, url: 'https://cu.syncio.co/images/random_image_4.png' },
    { id: 34546, position: 5, url: 'https://cu.syncio.co/images/random_image_16.png' },
    { id: 34566, position: 3, url: 'https://cu.syncio.co/images/random_image_7.png' }
  ],
  variants: [
    { id: 433232, sku: 'SKU-II-10', barcode: 'BAR_CODE_230', image_id: 34566, inventory_quantity: 12 },
    { id: 231544, sku: 'SKU-II-20', barcode: 'B231342313', image_id: 56353, inventory_quantity: 2 },
    { id: 323245, sku: 'SKU-II-10', barcode: 'BACDSDE_O', image_id: 26372, inventory_quantity: 8 },
    { id: 323445, sku: 'SKU-II-1о', barcode: 'AR_CO23DE_23', image_id: 33213, inventory_quantity: 0 }
  ]
};

const allChanges = computed(() => {
  const changes: Array<{ field: string; from: any; to: any }> = [];
  
  // Root changes
  const rc = diff.value?.rootChanges || {};
  for (const k of Object.keys(rc)) {
    changes.push({ field: k, from: rc[k].from, to: rc[k].to });
  }
  
  // Image changes
  for (const img of diff.value?.images?.changed || []) {
    for (const [field, change] of Object.entries(img.changes)) {
      changes.push({ field: `images[${img.id}].${field}`, from: change.from, to: change.to });
    }
  }
  
  // Variant changes
  for (const variant of diff.value?.variants?.changed || []) {
    for (const [field, change] of Object.entries(variant.changes)) {
      changes.push({ field: `variants[${variant.id}].${field}`, from: change.from, to: change.to });
    }
  }
  
  return changes;
});

function changesToArray(obj: Record<string, { from: any; to: any }>) {
  return Object.keys(obj).map((k) => ({ field: k, from: obj[k].from, to: obj[k].to }));
}

function findImageById(images: any[], id: number) {
  return images.find(img => img.id === id) || {};
}

function findVariantById(variants: any[], id: number) {
  return variants.find(v => v.id === id) || {};
}

// Reset all state to start a new comparison
function resetComparison() {
  ackP1.value = false;
  ackP2.value = false;
  sending.value = false;
  waitingSecond.value = false;
  status.value = 'idle';
  diff.value = {};
  error.value = null;
  payload1Error.value = null;
}

async function sendFirst() {
  try {
    sending.value = true;
    error.value = null;
    payload1Error.value = null;
    
    // Validate JSON
    let parsedPayload;
    try {
      parsedPayload = JSON.parse(payload1Json.value);
    } catch (e) {
      payload1Error.value = 'Invalid JSON format. Please fix the syntax.';
      return;
    }
    
    await axios.post('/api/payloads/first', parsedPayload, { baseURL: '/' });
    ackP1.value = true;

    waitingSecond.value = true;
    setTimeout(sendSecond, 30000);
  } catch (e: any) {
    error.value = e?.response?.data?.message || e?.message || 'Error sending payload 1';
  } finally {
    sending.value = false;
  }
}

async function sendSecond() {
  try {
    error.value = null;
    await axios.post('/api/payloads/second', payload2, { baseURL: '/' });
    ackP2.value = true;
    waitingSecond.value = false;
    await startPollingDiff();
  } catch (e: any) {
    error.value = e?.response?.data?.message || e?.message || 'Error sending payload 2';
    waitingSecond.value = false;
  }
}

async function startPollingDiff() {
  status.value = 'pending';
  diff.value = {};
  for (let i = 0; i < 20; i++) {
    try {
      const res = await axios.get('/api/diff', { baseURL: '/' });
      if (res.data?.status === 'done') {
        diff.value = res.data.diff;
        status.value = 'done';
        console.log('✅ Status changed to "done", button should appear now');
        return;
      }
    } catch (e: any) {
      error.value = e?.response?.data?.message || e?.message || 'Error fetching diff';
      break;
    }
    await new Promise(r => setTimeout(r, 1000));
  }
}
</script>

<style>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}
</style>

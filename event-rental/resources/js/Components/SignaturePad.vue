<script setup lang="ts">
import { ref, onMounted } from 'vue';

const emit = defineEmits(['signed']);

const canvas = ref<HTMLCanvasElement | null>(null);
const ctx = ref<CanvasRenderingContext2D | null>(null);
const isDrawing = ref(false);

onMounted(() => {
    if (canvas.value) {
        ctx.value = canvas.value.getContext('2d');
        if (ctx.value) {
            ctx.value.strokeStyle = '#4f46e5'; // Indigo-600
            ctx.value.lineWidth = 3;
            ctx.value.lineCap = 'round';
        }
    }
});

const startDrawing = (e: any) => {
    isDrawing.value = true;
    const { offsetX, offsetY } = getPointerPos(e);
    ctx.value?.beginPath();
    ctx.value?.moveTo(offsetX, offsetY);
};

const draw = (e: any) => {
    if (!isDrawing.value) return;
    const { offsetX, offsetY } = getPointerPos(e);
    ctx.value?.lineTo(offsetX, offsetY);
    ctx.value?.stroke();
};

const stopDrawing = () => {
    isDrawing.value = false;
};

const getPointerPos = (e: any) => {
    let offsetX, offsetY;
    if (e.type.startsWith('mouse')) {
        offsetX = e.offsetX;
        offsetY = e.offsetY;
    } else {
        const rect = canvas.value!.getBoundingClientRect();
        offsetX = e.touches[0].clientX - rect.left;
        offsetY = e.touches[0].clientY - rect.top;
    }
    return { offsetX, offsetY };
};

const clearSession = () => {
    ctx.value?.clearRect(0, 0, canvas.value!.width, canvas.value!.height);
};

const saveSession = () => {
    const signatureBase64 = canvas.value?.toDataURL('image/png');
    emit('signed', signatureBase64);
};
</script>

<template>
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <h4 class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-widest">Digital Signature Pad</h4>
        <canvas 
            ref="canvas"
            width="600"
            height="250"
            class="bg-gray-50 border border-dashed border-gray-300 rounded-xl cursor-crosshair touch-none mb-6 w-full"
            @mousedown="startDrawing"
            @mousemove="draw"
            @mouseup="stopDrawing"
            @mouseleave="stopDrawing"
            @touchstart="startDrawing"
            @touchmove="draw"
            @touchend="stopDrawing"
        ></canvas>

        <div class="flex justify-between items-center">
            <div class="text-[10px] text-gray-400 font-medium">
                WITBO AUDIT: SECURE SIGNATURE CAPTURE ACTIVE
            </div>
            <div class="flex gap-3">
                <button @click="clearSession" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-rose-600 transition-colors">Clear</button>
                <button @click="saveSession" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-xl text-xs shadow-sm shadow-indigo-100 transition-colors">Confirm & Submit</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.signature-container {
    max-width: 600px;
}
</style>

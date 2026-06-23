<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import * as echarts from 'echarts/core';
import { PieChart } from 'echarts/charts';
import { TooltipComponent, LegendComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([PieChart, TooltipComponent, LegendComponent, CanvasRenderer]);

const emit = defineEmits(['categoryClick']);
const props = defineProps({ data: Array });
const chartRef = ref(null);
let chart = null;

const hasData = computed(() => {
    return props.data && props.data.length > 0 && props.data.some(item => (item.value || 0) > 0);
});

function formatRp(val) {
    if (val == null) return 'Rp 0';
    if (val >= 1e9) return 'Rp ' + (val / 1e9).toFixed(1) + 'M';
    if (val >= 1e6) return 'Rp ' + (val / 1e6).toFixed(1) + 'jt';
    return 'Rp ' + val.toLocaleString('id-ID');
}

const PALETTE = [
    '#E8637A', '#C49A4A', '#F4899A', '#34d399', '#60a5fa',
    '#f59e0b', '#a78bfa', '#f472b6', '#2dd4bf', '#fb923c',
];

function getContainerWidth() {
    return chartRef.value?.clientWidth || 400;
}

function buildOption() {
    const d = (props.data || []).map((item, i) => ({
        ...item,
        color: item.name === 'Belum Diklasifikasi' ? '#CBD5E1' : (item.color === '#E8637A' ? PALETTE[i % PALETTE.length] : (item.color || PALETTE[i % PALETTE.length])),
    }));
    const total = d.reduce((s, i) => s + (i.value || 0), 0);
    const hasMultiple = d.length > 1;
    const w = getContainerWidth();
    const isMobile = w < 480;

    return {
        tooltip: {
            trigger: 'item',
            backgroundColor: '#fff',
            borderColor: '#FFD0D6',
            borderWidth: 1,
            textStyle: { color: '#2C1929', fontSize: 12 },
            formatter: (p) => `<b>${p.name}</b><br/>${formatRp(p.value)} (${(p.percent).toFixed(1)}%)`,
            confine: true,
        },
        legend: isMobile ? {
            orient: 'horizontal',
            bottom: 0,
            left: 'center',
            textStyle: { color: '#635850', fontSize: 10 },
            itemWidth: 8, itemHeight: 8, itemGap: 6,
            formatter: (name) => name.length > 14 ? name.substring(0, 14) + '…' : name,
        } : {
            orient: 'vertical',
            right: 10,
            top: 'center',
            textStyle: { color: '#635850', fontSize: 11 },
            itemWidth: 10, itemHeight: 10, itemGap: 8,
            formatter: (name) => name.length > 20 ? name.substring(0, 20) + '…' : name,
        },
        animationDuration: 1000,
        animationEasing: 'cubicOut',
        series: [{
            type: 'pie',
            radius: isMobile ? ['38%', '62%'] : ['45%', '70%'],
            center: isMobile ? ['50%', '42%'] : ['38%', '50%'],
            avoidLabelOverlap: true,
            label: {
                show: true,
                position: 'center',
                formatter: () => `{total|${formatRp(total)}}\n{sub|Total}`,
                rich: {
                    total: { fontSize: isMobile ? 13 : 15, fontWeight: 'bold', color: '#2C1929', lineHeight: isMobile ? 18 : 22 },
                    sub: { fontSize: isMobile ? 10 : 11, color: '#8A7E70', lineHeight: isMobile ? 14 : 18 },
                },
            },
            emphasis: {
                label: { show: true, fontSize: isMobile ? 13 : 15, fontWeight: 'bold' },
                itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.1)' },
            },
            itemStyle: {
                borderRadius: hasMultiple ? 6 : 0,
                borderColor: '#fff',
                borderWidth: hasMultiple ? 2 : 0,
            },
            data: d.map(item => ({
                value: item.value,
                name: item.name,
                itemStyle: { color: item.color },
            })),
        }],
    };
}

let resizeObserver = null;

function initChart() {
    if (!chartRef.value) return;
    if (chart) chart.dispose();
    chart = echarts.init(chartRef.value);
    chart.setOption(buildOption());
    chart.on('click', 'series.pie', (params) => {
        const item = props.data?.[params.dataIndex];
        if (item && item.category_id != null) {
            emit('categoryClick', item.category_id);
        }
    });
}

onMounted(() => {
    nextTick(() => {
        initChart();

        if (chartRef.value) {
            resizeObserver = new ResizeObserver(() => {
                requestAnimationFrame(() => {
                    if (chartRef.value && chartRef.value.clientWidth > 0 && chart) {
                        chart.resize();
                        chart.setOption(buildOption(), true);
                    }
                });
            });
            resizeObserver.observe(chartRef.value);
        }
    });
});

watch(() => props.data, () => {
    if (chart) {
        chart.setOption(buildOption(), true);
        chart.off('click', 'series.pie');
        chart.on('click', 'series.pie', (params) => {
            const item = props.data?.[params.dataIndex];
            if (item && item.category_id != null) {
                emit('categoryClick', item.category_id);
            }
        });
    } else {
        nextTick(initChart);
    }
}, { deep: true });

onBeforeUnmount(() => {
    if (resizeObserver) resizeObserver.disconnect();
    chart?.dispose();
    chart = null;
});
</script>

<template>
    <div class="relative w-full h-full min-h-[240px] flex flex-col items-center justify-center">
        <!-- Chart Container -->
        <div v-show="hasData" ref="chartRef" class="w-full h-full min-h-[240px]"></div>

        <!-- Premium Empty State -->
        <div v-if="!hasData" class="flex flex-col items-center justify-center p-6 text-center animate-scale-in">
            <!-- Icon with beautiful background gradient -->
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-50 to-amber-50/50 flex items-center justify-center mb-4 border border-rose-100/40 shadow-soft">
                <svg class="w-8 h-8 text-rose-gold/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                </svg>
            </div>
            
            <h4 class="text-sm font-semibold text-plum mb-1">Belum Ada Data Transaksi</h4>
            <p class="text-xs text-surface-500 max-w-[240px] leading-relaxed">
                Tidak ditemukan transaksi untuk periode dan rekening yang Anda pilih.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import * as echarts from 'echarts/core';
import { BarChart, LineChart } from 'echarts/charts';
import { GridComponent, TooltipComponent, LegendComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([BarChart, LineChart, GridComponent, TooltipComponent, LegendComponent, CanvasRenderer]);

const props = defineProps({ data: Object });
const chartRef = ref(null);
let chart = null;

function formatRp(val) {
    if (val >= 1e9) return 'Rp ' + (val / 1e9).toFixed(1) + 'M';
    if (val >= 1e6) return 'Rp ' + (val / 1e6).toFixed(1) + 'jt';
    if (val >= 1e3) return 'Rp ' + (val / 1e3).toFixed(0) + 'rb';
    return 'Rp ' + val;
}

function buildOption() {
    const d = props.data;
    if (!d) return {};
    return {
        tooltip: {
            trigger: 'axis',
            backgroundColor: '#fff',
            borderColor: '#FDD5DF',
            borderWidth: 1,
            textStyle: { color: '#3D1F2B', fontSize: 12 },
            formatter: (params) => {
                let s = `<div style="font-weight:600;margin-bottom:4px">${params[0].axisValue}</div>`;
                params.forEach(p => {
                    s += `<div style="display:flex;align-items:center;gap:6px;margin:2px 0">
                        <span style="width:8px;height:8px;border-radius:50%;background:${p.color}"></span>
                        <span>${p.seriesName}: <b>${formatRp(p.value)}</b></span></div>`;
                });
                return s;
            },
        },
        legend: {
            bottom: 0,
            textStyle: { color: '#8C7D6E', fontSize: 11 },
            itemWidth: 12, itemHeight: 12, itemGap: 16,
        },
        grid: { left: 0, right: 8, top: 16, bottom: 40, containLabel: true },
        xAxis: {
            type: 'category',
            data: d.dates,
            axisLine: { lineStyle: { color: '#E8DDD1' } },
            axisLabel: { color: '#8C7D6E', fontSize: 10, rotate: d.dates?.length > 30 ? 45 : 0 },
            axisTick: { show: false },
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { color: '#F5EDE3', type: 'dashed' } },
            axisLabel: { color: '#8C7D6E', fontSize: 10, formatter: (v) => formatRp(v) },
        },
        series: [
            {
                name: 'Pemasukan', type: 'bar', data: d.debitData,
                itemStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#34d399' }, { offset: 1, color: '#10b981' }
                ]), borderRadius: [6, 6, 0, 0] },
                barMaxWidth: 24,
            },
            {
                name: 'Pengeluaran', type: 'bar', data: d.creditData,
                itemStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#fb7185' }, { offset: 1, color: '#e11d48' }
                ]), borderRadius: [6, 6, 0, 0] },
                barMaxWidth: 24,
            },
            {
                name: 'Arus Bersih', type: 'line', data: d.netData,
                lineStyle: { color: '#B76E79', width: 2 },
                itemStyle: { color: '#B76E79' },
                symbol: 'circle', symbolSize: 4,
                smooth: true,
            },
        ],
    };
}

let resizeObserver = null;

onMounted(() => {
    if (chartRef.value) {
        chart = echarts.init(chartRef.value);
        chart.setOption(buildOption());
        
        resizeObserver = new ResizeObserver(() => {
            if (chartRef.value && chartRef.value.clientWidth > 0) {
                chart.resize();
            }
        });
        resizeObserver.observe(chartRef.value);
    }
});

watch(() => props.data, () => {
    chart?.setOption(buildOption(), true);
}, { deep: true });

onBeforeUnmount(() => {
    if (resizeObserver) resizeObserver.disconnect();
    chart?.dispose();
});
</script>

<template>
    <div ref="chartRef" class="w-full h-full"></div>
</template>

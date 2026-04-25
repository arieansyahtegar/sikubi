<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import * as echarts from 'echarts/core';
import { PieChart } from 'echarts/charts';
import { TooltipComponent, LegendComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([PieChart, TooltipComponent, LegendComponent, CanvasRenderer]);

const props = defineProps({ data: Array });
const chartRef = ref(null);
let chart = null;

function formatRp(val) {
    if (val >= 1e9) return 'Rp ' + (val / 1e9).toFixed(1) + 'M';
    if (val >= 1e6) return 'Rp ' + (val / 1e6).toFixed(1) + 'jt';
    return 'Rp ' + val?.toLocaleString('id-ID');
}

function buildOption() {
    const d = props.data || [];
    const total = d.reduce((s, i) => s + (i.value || 0), 0);
    return {
        tooltip: {
            trigger: 'item',
            backgroundColor: '#fff',
            borderColor: '#FDD5DF',
            borderWidth: 1,
            textStyle: { color: '#3D1F2B', fontSize: 12 },
            formatter: (p) => `<b>${p.name}</b><br/>${formatRp(p.value)} (${p.percent}%)`,
        },
        legend: {
            orient: 'vertical',
            right: 0,
            top: 'center',
            textStyle: { color: '#655849', fontSize: 11 },
            itemWidth: 10, itemHeight: 10, itemGap: 8,
            formatter: (name) => {
                const item = d.find(i => i.name === name);
                return name.length > 14 ? name.substring(0, 14) + '…' : name;
            },
        },
        series: [{
            type: 'pie',
            radius: ['50%', '75%'],
            center: ['35%', '50%'],
            avoidLabelOverlap: true,
            label: {
                show: true,
                position: 'center',
                formatter: () => `{total|${formatRp(total)}}\n{sub|Total}`,
                rich: {
                    total: { fontSize: 16, fontWeight: 'bold', color: '#3D1F2B', lineHeight: 22 },
                    sub: { fontSize: 11, color: '#8C7D6E', lineHeight: 18 },
                },
            },
            itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
            data: d.map(item => ({
                value: item.value,
                name: item.name,
                itemStyle: { color: item.color },
            })),
        }],
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

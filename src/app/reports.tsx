import { router } from 'expo-router';
import { ArrowLeft, Inbox, Plus } from 'lucide-react-native';
import { useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Aurora } from '@/components/Aurora';
import { Chip, Empty } from '@/components/bits';
import { Glass } from '@/components/Glass';
import { ReportRow } from '@/components/ReportRow';
import { T } from '@/components/Text';
import { useReports } from '@/lib/queries';
import { useTheme } from '@/lib/theme';

const filters = [
  { value: 'all', label: 'All' },
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'resolved', label: 'Resolved' },
];

export default function Reports() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const { data, isLoading, refetch, isRefetching, error } = useReports();
  const [filter, setFilter] = useState('all');
  const list = useMemo(() => (filter === 'all' ? data ?? [] : (data ?? []).filter((r) => r.status === filter)), [data, filter]);

  return (
    <View style={{ flex: 1 }}>
      <Aurora intensity={0.8} />
      <FlatList
        data={list}
        keyExtractor={(r) => String(r.id)}
        contentContainerStyle={{ paddingTop: insets.top + 12, paddingBottom: insets.bottom + 30, paddingHorizontal: 18, gap: 10 }}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => void refetch()} tintColor={theme.primary} colors={[theme.primary]} />}
        ListHeaderComponent={
          <View style={{ gap: 16, marginBottom: 6 }}>
            <View style={styles.head}>
              <Pressable onPress={() => router.back()} hitSlop={12} style={styles.icon} accessibilityLabel="Back">
                <ArrowLeft size={21} color={theme.foreground} />
              </Pressable>
              <T variant="title" style={{ flex: 1 }}>
                My reports
              </T>
              <Pressable onPress={() => router.push('/report')} hitSlop={12} style={[styles.icon, { backgroundColor: theme.primary }]} accessibilityLabel="New report">
                <Plus size={21} color={theme.primaryForeground} />
              </Pressable>
            </View>
            <View style={styles.filters}>
              {filters.map((f) => (
                <Chip key={f.value} label={f.label} selected={filter === f.value} onPress={() => setFilter(f.value)} />
              ))}
            </View>
          </View>
        }
        renderItem={({ item, index }) => (
          <Animated.View entering={FadeInDown.delay(Math.min(index, 8) * 45).springify().damping(18)}>
            <Glass radius={20} style={{ paddingHorizontal: 14 }}>
              <ReportRow r={item} last />
            </Glass>
          </Animated.View>
        )}
        ListEmptyComponent={
          <Glass radius={24}>
            <Empty
              icon={<Inbox size={28} color={theme.muted} />}
              text={isLoading ? 'Loading your reports…' : error ? 'Could not load reports. Pull down to retry.' : filter === 'all' ? 'No reports yet. Anonymous reports are not listed here.' : 'Nothing with this status.'}
            />
          </Glass>
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  head: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  icon: { width: 42, height: 42, borderRadius: 21, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.08)' },
  filters: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});

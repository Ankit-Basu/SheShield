import * as Haptics from 'expo-haptics';
import { CircleCheck, Paperclip } from 'lucide-react-native';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeIn, LinearTransition } from 'react-native-reanimated';

import { typeLabel } from '@/lib/campus';
import { shortDateTime } from '@/lib/format';
import type { Report } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

import { Badge } from './bits';
import { T } from './Text';

const severityColor = { high: '#ff6b6b', medium: status.warn, low: status.info } as const;
const severityLabel = { high: 'High', medium: 'Medium', low: 'Low' } as const;

type Props = {
  r: Report;
  last?: boolean;
  /** Tapping the row shows the full report. */
  expanded?: boolean;
  onPress?: () => void;
  onResolve?: () => void;
  resolving?: boolean;
};

// One report as a list row: a severity dot, the type and its status, then where and
// when, then what happened. Expanded, it shows the whole description and, for your
// own pending reports, a way to mark it resolved.
export function ReportRow({ r, last, expanded, onPress, onResolve, resolving }: Props) {
  const theme = useTheme();
  const dot = severityColor[r.severity as keyof typeof severityColor] ?? theme.muted;
  const body = (
    <Animated.View layout={LinearTransition.springify().damping(20)} style={[styles.row, !last && styles.divider]}>
      <View style={[styles.dot, { backgroundColor: dot }]} />
      <View style={{ flex: 1, gap: 3 }}>
        <View style={styles.top}>
          <T variant="label" numberOfLines={1} style={{ flexShrink: 1 }}>
            {typeLabel(r.type)}
          </T>
          <Badge value={r.status} />
        </View>
        <View style={styles.meta}>
          <T variant="small" muted numberOfLines={1} style={{ flexShrink: 1 }}>
            {r.location} · {shortDateTime(r.date_time)}
          </T>
          {r.has_evidence ? <Paperclip size={12} color={theme.muted} /> : null}
        </View>
        <T variant="small" numberOfLines={expanded ? undefined : 1} style={{ opacity: expanded ? 0.95 : 0.75, lineHeight: 19 }}>
          {r.description}
        </T>
        {expanded && (
          <Animated.View entering={FadeIn.duration(200)} style={styles.details}>
            <View style={styles.facts}>
              <T variant="small" muted>
                Severity <T variant="small" color={dot}>{severityLabel[r.severity as keyof typeof severityLabel] ?? r.severity}</T>
              </T>
              <T variant="small" muted>
                Report #{r.id}
              </T>
              {r.has_evidence ? (
                <T variant="small" muted>
                  Evidence attached
                </T>
              ) : null}
            </View>
            {r.status === 'pending' && onResolve ? (
              <Pressable
                onPress={() => {
                  Haptics.selectionAsync();
                  onResolve();
                }}
                disabled={resolving}
                style={[styles.resolve, { borderColor: status.good + '66', opacity: resolving ? 0.6 : 1 }]}
                accessibilityRole="button"
                accessibilityLabel="Mark this report as resolved">
                <CircleCheck size={15} color={status.good} />
                <T variant="small" color={status.good}>
                  {resolving ? 'Marking…' : 'Mark as resolved'}
                </T>
              </Pressable>
            ) : null}
          </Animated.View>
        )}
      </View>
    </Animated.View>
  );
  if (!onPress) return body;
  return (
    <Pressable
      onPress={() => {
        Haptics.selectionAsync();
        onPress();
      }}
      accessibilityRole="button"
      accessibilityState={{ expanded: !!expanded }}
      accessibilityLabel={`${typeLabel(r.type)}, ${r.status.replace('_', ' ')}. ${expanded ? 'Hide' : 'Show'} details`}>
      {body}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: 12, paddingVertical: 14 },
  divider: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: 'rgba(255,255,255,0.12)' },
  dot: { width: 8, height: 8, borderRadius: 4, marginTop: 6 },
  top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  meta: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  details: { gap: 10, marginTop: 6 },
  facts: { flexDirection: 'row', flexWrap: 'wrap', columnGap: 14, rowGap: 4 },
  resolve: { flexDirection: 'row', alignItems: 'center', gap: 6, alignSelf: 'flex-start', paddingHorizontal: 12, height: 34, borderRadius: 17, borderWidth: 1 },
});

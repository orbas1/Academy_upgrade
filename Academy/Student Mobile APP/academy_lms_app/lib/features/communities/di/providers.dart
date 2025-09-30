import 'package:provider/provider.dart';
import 'package:provider/single_child_widget.dart';

import '../../../providers/auth.dart';
import '../data/community_cache.dart';
import '../data/offline_action_queue.dart';
import '../data/queue_health_repository.dart';
import '../state/community_notifier.dart';
import '../state/community_onboarding_notifier.dart';
import '../state/community_presence_notifier.dart';
import '../../../services/realtime/realtime_presence_service.dart';
import '../../../providers/presence_controller.dart';
import '../../../services/analytics/mobile_analytics_service.dart';

List<SingleChildWidget> communityProviders({CommunityCache? cache}) {
  final sharedCache = cache ?? CommunityCache();

  return <SingleChildWidget>[
    Provider<CommunityCache>.value(value: sharedCache),
    Provider<OfflineCommunityActionQueue>(
      create: (_) => OfflineCommunityActionQueue(),
      dispose: (_, queue) {
        queue.close();
      },
    ),
    Provider<RealtimePresenceService>(
      create: (_) => RealtimePresenceService(),
      dispose: (_, service) => service.dispose(),
    ),
    Provider<MobileAnalyticsService>(
      create: (_) => MobileAnalyticsService.instance,
    ),
    ChangeNotifierProxyProvider<Auth, PresenceController>(
      create: (_) => PresenceController(),
      update: (_, auth, controller) {
        final notifier = controller ?? PresenceController();
        notifier.updateAuthToken(auth.token);
        return notifier;
      },
    ),
    ChangeNotifierProvider<CommunityOnboardingNotifier>(
      create: (_) => CommunityOnboardingNotifier(),
    ),
    ChangeNotifierProxyProvider2<Auth, RealtimePresenceService, CommunityPresenceNotifier>(
      create: (_) => CommunityPresenceNotifier(),
      update: (_, auth, realtime, notifier) {
        final controller = notifier ?? CommunityPresenceNotifier();
        controller.attachService(realtime);
        controller.updateAuthToken(auth.token);
        return controller;
      },
    ),
    ProxyProvider<Auth, QueueHealthRepository>(
      update: (_, auth, repository) {
        final repo = repository ?? QueueHealthRepository();
        repo.updateAuthToken(auth.token);
        return repo;
      },
    ),
    ChangeNotifierProxyProvider4<Auth, QueueHealthRepository,
        OfflineCommunityActionQueue, CommunityPresenceNotifier, CommunityNotifier>(
      create: (context) => CommunityNotifier(
        queueHealthRepository: context.read<QueueHealthRepository>(),
        cache: sharedCache,
        offlineQueue: context.read<OfflineCommunityActionQueue>(),
        presenceNotifier: context.read<CommunityPresenceNotifier>(),
        analytics: context.read<MobileAnalyticsService>(),
      ),
      update: (_, auth, queueRepo, offlineQueue, presenceNotifier, notifier) {
        final controller = notifier ??
            CommunityNotifier(
              queueHealthRepository: queueRepo,
              cache: sharedCache,
              offlineQueue: offlineQueue,
              presenceNotifier: presenceNotifier,
              analytics: context.read<MobileAnalyticsService>(),
            );

        controller.updateAuthToken(auth.token);
        controller.updateQueueHealthRepository(queueRepo);
        controller.updateOfflineQueue(offlineQueue);
        controller.updatePresenceNotifier(presenceNotifier);
        return controller;
      },
    ),
  ];
}

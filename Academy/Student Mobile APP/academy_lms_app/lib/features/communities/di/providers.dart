import 'package:provider/provider.dart';
import 'package:provider/single_child_widget.dart';

import '../../../providers/auth.dart';
import '../data/community_cache.dart';
import '../data/offline_action_queue.dart';
import '../data/queue_health_repository.dart';
import '../state/community_notifier.dart';
import '../state/community_onboarding_notifier.dart';

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
    ChangeNotifierProvider<CommunityOnboardingNotifier>(
      create: (_) => CommunityOnboardingNotifier(),
    ),
    ProxyProvider<Auth, QueueHealthRepository>(
      update: (_, auth, repository) {
        final repo = repository ?? QueueHealthRepository();
        repo.updateAuthToken(auth.token);
        return repo;
      },
    ),
    ChangeNotifierProxyProvider3<Auth, QueueHealthRepository,
        OfflineCommunityActionQueue, CommunityNotifier>(
      create: (context) => CommunityNotifier(
        queueHealthRepository: QueueHealthRepository(),
        cache: sharedCache,
        offlineQueue: context.read<OfflineCommunityActionQueue>(),
      ),
      update: (_, auth, queueRepo, offlineQueue, notifier) {
        final controller = notifier ??
            CommunityNotifier(
              queueHealthRepository: queueRepo,
              cache: sharedCache,
              offlineQueue: offlineQueue,
            );

        controller.updateAuthToken(auth.token);
        controller.updateQueueHealthRepository(queueRepo);
        controller.updateOfflineQueue(offlineQueue);
        return controller;
      },
    ),
  ];
}

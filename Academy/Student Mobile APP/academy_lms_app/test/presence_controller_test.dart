import 'dart:async';

import 'package:academy_lms_app/providers/presence_controller.dart';
import 'package:academy_lms_app/services/realtime/presence_channel.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('presence controller tracks realtime membership lifecycle', () async {
    final fakeChannel = FakePresenceChannel();
    final controller = PresenceController(channelFactory: () => fakeChannel);

    await controller.connectToCommunity('community-1');
    expect(controller.state, PresenceConnectionState.connecting);

    fakeChannel.emit(PresenceMessage.connection(PresenceConnectionState.connected));
    await pumpEventQueue();
    expect(controller.state, PresenceConnectionState.connected);

    final alice = PresenceMember(
      memberId: '1',
      displayName: 'Alice',
      lastSeenAt: DateTime.utc(2024, 1, 1),
    );
    fakeChannel.emit(PresenceMessage.initial(<PresenceMember>[alice]));
    await pumpEventQueue();

    expect(controller.onlineCount, 1);
    expect(controller.members.first.memberId, '1');

    final bob = PresenceMember(
      memberId: '2',
      displayName: 'Bob',
      lastSeenAt: DateTime.utc(2024, 1, 2),
    );
    fakeChannel.emit(PresenceMessage.join(bob));
    await pumpEventQueue();

    expect(controller.onlineCount, 2);

    fakeChannel.emit(PresenceMessage.leave(alice));
    await pumpEventQueue();

    expect(controller.onlineCount, 1);
    expect(controller.members.first.memberId, '2');

    fakeChannel.emit(PresenceMessage.error(Exception('network')));
    await pumpEventQueue();

    expect(controller.state, PresenceConnectionState.error);
    expect(controller.errorMessage, contains('network'));

    await controller.disconnect();
    expect(controller.onlineCount, 0);
    expect(controller.state, PresenceConnectionState.disconnected);
  });
}

class FakePresenceChannel implements PresenceChannel {
  FakePresenceChannel();

  final StreamController<PresenceMessage> _controller =
      StreamController<PresenceMessage>.broadcast();

  @override
  Stream<PresenceMessage> get events => _controller.stream;

  @override
  Future<void> connect({required String communityId, String? authToken}) async {}

  @override
  Future<void> disconnect() async {}

  @override
  Future<void> dispose() async {}

  void emit(PresenceMessage message) {
    _controller.add(message);
  }
}

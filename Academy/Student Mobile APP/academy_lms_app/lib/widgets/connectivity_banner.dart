import 'dart:async';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';

class ConnectivityBanner extends StatefulWidget {
  const ConnectivityBanner({super.key, this.connectivity});

  final Connectivity? connectivity;

  @override
  State<ConnectivityBanner> createState() => _ConnectivityBannerState();
}

class _ConnectivityBannerState extends State<ConnectivityBanner> {
  late final Connectivity _connectivity;
  StreamSubscription<ConnectivityResult>? _subscription;
  bool _isOffline = false;

  @override
  void initState() {
    super.initState();
    _connectivity = widget.connectivity ?? Connectivity();
    _subscription = _connectivity.onConnectivityChanged.listen(_handleResult);
    _connectivity.checkConnectivity().then(_handleResult);
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }

  void _handleResult(ConnectivityResult result) {
    final offline = result == ConnectivityResult.none;
    if (offline != _isOffline && mounted) {
      setState(() {
        _isOffline = offline;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 250),
      switchInCurve: Curves.easeInOut,
      switchOutCurve: Curves.easeInOut,
      child: _isOffline
          ? Container(
              key: const ValueKey<String>('offline-banner'),
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.orange.shade700,
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(12),
                  bottomRight: Radius.circular(12),
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: const [
                  Icon(Icons.wifi_off, color: Colors.white),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'You are offline. Recent actions will sync automatically once the connection is restored.',
                      style: TextStyle(color: Colors.white),
                    ),
                  ),
                ],
              ),
            )
          : const SizedBox.shrink(),
    );
  }
}

// ignore_for_file: constant_identifier_names, camel_case_types
import 'package:shared_preferences/shared_preferences.dart';

import '../services/security/secure_credential_store.dart';

class SharedPreferenceHelper {
  Future<bool> setAuthToken(String token) async {
    await SecureCredentialStore.instance.persistAccessToken(token);
    final pref = await SharedPreferences.getInstance();
    if (pref.containsKey(userPref.AuthToken.toString())) {
      await pref.remove(userPref.AuthToken.toString());
    }
    return true;
  }

  Future<String?> getAuthToken() async {
    final secureToken = await SecureCredentialStore.instance.readAccessToken();
    if (secureToken != null && secureToken.isNotEmpty) {
      return secureToken;
    }

    final pref = await SharedPreferences.getInstance();
    return pref.getString(userPref.AuthToken.toString());
  }

  // Future<bool> setUserData(String userData) async {
  //   final pref = await SharedPreferences.getInstance();
  //   return pref.setString(userPref.UserData.toString(), userData);
  // }
  //
  // Future<String?> getUserData() async {
  //   final pref = await SharedPreferences.getInstance();
  //   return pref.getString(userPref.UserData.toString());
  // }

  Future<bool> setUserImage(String image) async {
    final pref = await SharedPreferences.getInstance();
    return pref.setString(userPref.Image.toString(), image);
  }

  Future<String?> getUserImage() async {
    final pref = await SharedPreferences.getInstance();
    return pref.getString(userPref.Image.toString());
  }
}

enum userPref {
  AuthToken,
  Image,
}

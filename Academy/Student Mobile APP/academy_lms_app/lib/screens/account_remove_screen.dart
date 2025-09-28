// ignore_for_file: use_build_context_synchronously, duplicate_ignore

import 'dart:convert';

import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../constants.dart';
import '../providers/auth.dart';
import '../widgets/common_functions.dart';

class AccountRemoveScreen extends StatefulWidget {
  static const routeName = '/account_delete';
  const AccountRemoveScreen({super.key});

  @override
  State<AccountRemoveScreen> createState() => _AccountRemoveScreenState();
}

class _AccountRemoveScreenState extends State<AccountRemoveScreen> {
  TextEditingController passwordController = TextEditingController();

  bool hidePassword = true;

  accountDelete() async {
    final prefs = await SharedPreferences.getInstance();
    final authToken = (prefs.getString('access_token') ?? '');

    var url = "$baseUrl/api/account_disable";

    final response = await http.post(
      Uri.parse(url),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      },
      body: json.encode({
        'account_password': passwordController.text,
      }),
    );

    var data = jsonDecode(response.body);

    if (data['validity'] == 1) {
      CommonFunctions.showSuccessToast(data['message']);

      // ignore: use_build_context_synchronously
      Provider.of<Auth>(context, listen: false).logout().then((_) =>
          Navigator.pushNamedAndRemoveUntil(context, '/home', (r) => false));
    } else {
      CommonFunctions.showWarningToast(data['message']);
    }

    passwordController.clear();
  }

  @override
  void initState() {
    super.initState();
  }

  InputDecoration getInputDecoration(String hintext, IconData iconData) {
    return InputDecoration(
      enabledBorder: kDefaultInputBorder,
      focusedBorder: kDefaultFocusInputBorder,
      focusedErrorBorder: kDefaultFocusErrorBorder,
      errorBorder: kDefaultFocusErrorBorder,
      filled: true,
      hintStyle: const TextStyle(color: kFormInputColor),
      hintText: hintext,
      fillColor: Colors.white70,
      prefixIcon: Icon(
        iconData,
        color: kFormInputColor,
      ),
      suffixIcon: IconButton(
        onPressed: () {
          setState(() {
            hidePassword = !hidePassword;
          });
        },
        color: kTextLowBlackColor,
        icon: Icon(hidePassword
            ? Icons.visibility_off_outlined
            : Icons.visibility_outlined),
      ),
      contentPadding: const EdgeInsets.symmetric(vertical: 5),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      appBar: const AppBarOne(logo: 'light_logo.png'),
      body: SingleChildScrollView(
        child: SizedBox(
          // height: MediaQuery.of(context).size.height * .40,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
            child: Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
              elevation: 0.1,
              child: Column(
                children: [
                  const Padding(
                    padding: EdgeInsets.only(top: 10.0),
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: kPrimaryColor,
                        radius: 20,
                        child: Padding(
                          padding: EdgeInsets.all(6),
                          child: FittedBox(
                            child: Icon(
                              Icons.person_remove_outlined,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                      title: Text(
                        "Delete Your Account",
                        style: TextStyle(
                            fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                   Padding(
                    padding: EdgeInsets.symmetric(horizontal: 20),
                    child: Divider(
                      height: 5,
                      thickness: 1,
                      color: Colors.grey.shade300,
                    ),
                  ),
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    child: Text(
                      "When you delete your account you will lose accesss to your account services and we permanentlly delete your personal data.",
                      style:
                          TextStyle(fontSize: 14, fontWeight: FontWeight.w400),
                    ),
                  ),
                  Align(
                    alignment: Alignment.bottomRight,
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: SizedBox(
                        width: 100, // Adjust the button size as needed
                        height: 45, // Adjust the button size as needed
                        child: MaterialButton(
                          elevation: 0,
                          color: kPrimaryColor,
                          onPressed: () {
                            passwordController.text = '';
                            showDialog(
                              context: context,
                              builder: (BuildContext context) =>
                                  buildPopupDialog(context),
                            );
                          },
                          // padding: const EdgeInsets.symmetric(
                          //   horizontal: 20, vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadiusDirectional.circular(10),
                          ),
                          child: const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                'Delete',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.white,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  buildPopupDialog(BuildContext context) {
    // ignore: no_leading_underscores_for_local_identifiers
    StateSetter _setState;
    return AlertDialog(
      backgroundColor: kBackgroundColor,
      titlePadding: EdgeInsets.zero,
      title: const Padding(
        padding: EdgeInsets.only(left: 20.0, right: 20, top: 20),
        child: Text('Notifying',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w600)),
      ),
      contentPadding: EdgeInsets.zero,
      content: StatefulBuilder(
        builder: (BuildContext context, StateSetter setState) {
          _setState = setState;
          return Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 20.0, vertical: 10),
                child: Text(
                    'To remove your account provide your account password.',
                    style: TextStyle(fontSize: 13)),
              ),
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                child: TextFormField(
                  style: const TextStyle(fontSize: 12),
                  obscureText: hidePassword,
                  decoration: InputDecoration(
                    enabledBorder: kDefaultInputBorder,
                    focusedBorder: kDefaultFocusInputBorder,
                    focusedErrorBorder: kDefaultFocusErrorBorder,
                    errorBorder: kDefaultFocusErrorBorder,
                    filled: true,
                    hintStyle: const TextStyle(color: kFormInputColor),
                    hintText: 'password',
                    fillColor: Colors.white70,
                    prefixIcon: const Icon(
                      Icons.key_outlined,
                      color: kFormInputColor,
                    ),
                    suffixIcon: IconButton(
                      onPressed: () {
                        _setState(() {
                          hidePassword = !hidePassword;
                        });
                      },
                      color: kTextLowBlackColor,
                      icon: Icon(hidePassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined),
                    ),
                    contentPadding: const EdgeInsets.symmetric(vertical: 5),
                  ),
                  controller: passwordController,
                  keyboardType: TextInputType.text,
                  validator: (value) {
                    if (value!.isEmpty) {
                      return 'Pssword cannot be empty';
                    }
                    return null;
                  },
                  onChanged: (value) {
                    _setState(() {
                      passwordController.text = value;
                    });
                  },
                ),
              ),
            ],
          );
        },
      ),
      actions: <Widget>[
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10.0),
          child: Row(
            children: [
              Expanded(
                flex: 1,
                child: MaterialButton(
                  elevation: 0,
                  color: kRedColor,
                  onPressed: () {
                    Navigator.of(context).pop();
                  },
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadiusDirectional.circular(6),
                    // side: const BorderSide(color: kPrimaryColor),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'No',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                flex: 1,
                child: MaterialButton(
                  elevation: 0,
                  color: kPrimaryColor,
                  onPressed: () {
                    Navigator.of(context).pop();

                    accountDelete();
                  },
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadiusDirectional.circular(6),
                    // side: const BorderSide(color: kPrimaryColor),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Confirm',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
      ],
    );
  }
}

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth.dart';
import '../widgets/common_functions.dart';

class UpdatePasswordScreen extends StatefulWidget {
  const UpdatePasswordScreen({super.key});

  @override
  State<UpdatePasswordScreen> createState() => _UpdatePasswordScreenState();
}

class _UpdatePasswordScreenState extends State<UpdatePasswordScreen> {
  GlobalKey<FormState> globalFormKey = GlobalKey<FormState>();
  final GlobalKey<FormState> _formKey = GlobalKey();

  bool hideCurrentPassword = true;
  bool hideNewPassword = true;
  bool hideConfirmPassword = true;
  bool _isLoading = false;
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  final Map<String, String> _passwordData = {
    'current_password': '',
    'new_password': '',
    'confirm_password': '',
  };

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      // Invalid!
      return;
    }
    _formKey.currentState!.save();
    setState(() {
      _isLoading = true;
    });

    _passwordData['current_password'] = _currentPasswordController.text;
    _passwordData['new_password'] = _newPasswordController.text;
    _passwordData['confirm_password'] = _confirmPasswordController.text;

    try {
      await Provider.of<Auth>(context, listen: false).updateUserPassword(
          _passwordData['current_password'].toString(),
          _passwordData['new_password'].toString(),
          _passwordData['confirm_password'].toString());

      CommonFunctions.showSuccessToast('Password updated Successfully');
    } catch (error) {
      // ignore: use_build_context_synchronously
      CommonFunctions.showErrorDialog(error.toString(), context);
    }
    setState(() {
      _isLoading = false;
    });
  }

  InputDecoration getInputDecoration(String hintext) {
    return InputDecoration(
      enabledBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      border: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedErrorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      errorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      filled: true,
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintext,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarOne(),
      backgroundColor: kBackGroundColor,
      body: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const SizedBox(
                height: 20,
              ),
              const Center(
                child: Text(
                  'Update Password',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const SizedBox(
                height: 20,
              ),
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20.0, vertical: 5.0),
                child: TextFormField(
                  style: const TextStyle(color: Colors.black),
                  keyboardType: TextInputType.text,
                  controller: _currentPasswordController,
                  onSaved: (input) {
                    _currentPasswordController.text = input as String;
                  },
                  validator: (input) => input!.length < 3
                      ? "Password should be more than 3 characters"
                      : null,
                  obscureText: hideCurrentPassword,
                  decoration: InputDecoration(
                    enabledBorder: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    border: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    filled: true,
                    hintStyle:
                        const TextStyle(color: Colors.black54, fontSize: 14),
                    hintText: "Current Password",
                    fillColor: kInputBoxBackGroundColor,
                    contentPadding: const EdgeInsets.symmetric(
                        vertical: 18, horizontal: 15),
                    suffixIcon: IconButton(
                      onPressed: () {
                        setState(() {
                          hideCurrentPassword = !hideCurrentPassword;
                        });
                      },
                      color: kInputBoxIconColor,
                      icon: Icon(hideCurrentPassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined),
                    ),
                  ),
                ),
              ),
              const SizedBox(
                height: 10,
              ),
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20.0, vertical: 5.0),
                child: TextFormField(
                  // cursorColor: kBlackColor,
                  // cursorRadius: const Radius.circular(100),
                  style: const TextStyle(color: Colors.black),
                  keyboardType: TextInputType.text,
                  controller: _newPasswordController,
                  onSaved: (input) {
                    _newPasswordController.text = input as String;
                  },
                  validator: (input) => input!.length < 3
                      ? "Password should be more than 3 characters"
                      : null,
                  obscureText: hideNewPassword,
                  decoration: InputDecoration(
                    enabledBorder: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    border: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    filled: true,
                    hintStyle:
                        const TextStyle(color: Colors.black54, fontSize: 14),
                    hintText: "New Password",
                    fillColor: kInputBoxBackGroundColor,
                    contentPadding: const EdgeInsets.symmetric(
                        vertical: 18, horizontal: 15),
                    suffixIcon: IconButton(
                      onPressed: () {
                        setState(() {
                          hideNewPassword = !hideNewPassword;
                        });
                      },
                      color: kInputBoxIconColor,
                      icon: Icon(hideNewPassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined),
                    ),
                  ),
                ),
              ),
              const SizedBox(
                height: 10,
              ),
              Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20.0, vertical: 5.0),
                child: TextFormField(
                  style: const TextStyle(color: Colors.black),
                  keyboardType: TextInputType.text,
                  controller: _confirmPasswordController,
                  onSaved: (input) {
                    _confirmPasswordController.text = input as String;
                  },
                  validator: (input) => input!.length < 3
                      ? "Password should be more than 3 characters"
                      : null,
                  obscureText: hideConfirmPassword,
                  decoration: InputDecoration(
                    enabledBorder: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    border: OutlineInputBorder(
                      borderRadius:
                          const BorderRadius.all(Radius.circular(16.0)),
                      borderSide: BorderSide(
                          color: kDefaultColor.withOpacity(0.1), width: 1),
                    ),
                    filled: true,
                    hintStyle:
                        const TextStyle(color: Colors.black54, fontSize: 14),
                    hintText: "Confirm Password",
                    fillColor: kInputBoxBackGroundColor,
                    contentPadding: const EdgeInsets.symmetric(
                        vertical: 18, horizontal: 15),
                    suffixIcon: IconButton(
                      onPressed: () {
                        setState(() {
                          hideConfirmPassword = !hideConfirmPassword;
                        });
                      },
                      color: kInputBoxIconColor,
                      icon: Icon(hideConfirmPassword
                          ? Icons.visibility_off_outlined
                          : Icons.visibility_outlined),
                    ),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.only(top: 20.0),
                child: SizedBox(
                  width: double.infinity,
                  child: _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 20.0, vertical: 15.0),
                          child: MaterialButton(
                            elevation: 0,
                            color: kDefaultColor,
                            onPressed: _submit,
                            padding: const EdgeInsets.symmetric(
                                horizontal: 20, vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius:
                                  BorderRadiusDirectional.circular(16),
                              // side: const BorderSide(color: kRedColor),
                            ),
                            child: const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  'Update Now',
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
    );
  }
}

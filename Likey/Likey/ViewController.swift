//
//  ViewController.swift
//  Likey
//
//  Created by Gwion Rhys Davies on 25/08/2015.
//  Copyright (c) 2015 GwiTek. All rights reserved.
//

import UIKit
import Alamofire
import SwiftyJSON
import SIAlertView

class ViewController: UIViewController {

    @IBOutlet weak var beenLikedLabel: UILabel!
    override func viewDidLoad() {
        super.viewDidLoad()
        println(UIDevice.currentDevice().identifierForVendor.UUIDString)
        Alamofire.request(.POST, server_URL, parameters: ["auth": api_key, "action": "read_device", "device_id": UIDevice.currentDevice().identifierForVendor.UUIDString]).responseJSON { _, responce, JSONData, _ in
            if(responce?.statusCode as Int? == 200){
                let json = JSON(JSONData!)
                println(json[0]["been_liked"].int)
                if let beenLiked = json[0]["been_liked"].int{
                    self.beenLikedLabel.text="You have been liked \(beenLiked) times."
                }
            } else if(responce?.statusCode as Int? == 404){
                Alamofire.request(.POST, server_URL, parameters: ["auth": api_key, "action": "create_device", "device_id": UIDevice.currentDevice().identifierForVendor.UUIDString, "app_ver" : 1.0, "device_type":UIDevice.currentDevice().model]).responseJSON { _, responce, JSONData, _ in
                    if(responce?.statusCode as Int? == 200){
                        let json = JSON(JSONData!)
                        println(json[0]["been_liked"].int)
                        if let beenLiked = json[0]["been_liked"].int{
                            self.beenLikedLabel.text="You have been liked \(beenLiked) times."
                        }
                    }
                }
            }
        }
    }

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }
    
    @IBAction func likeButtonPressed(sender: UIButton) {
        Alamofire.request(.POST, server_URL, parameters: ["auth": api_key, "action": "device_liked", "device_id": UIDevice.currentDevice().identifierForVendor.UUIDString, ])
        //SIAlertView *alertView = [[SIAlertView alloc] initWithTitle:@"SIAlertView" andMessage:@"Sumi Interactive"];
        let alert = SIAlertView(title: "Thankyou", andMessage: "Thanks to you, you have made someones day a little brighter.")
        alert.addButtonWithTitle("Ok", type: SIAlertViewButtonType.Default, handler: { (alert) -> Void in
            
        })
        alert.show()
    }
}

